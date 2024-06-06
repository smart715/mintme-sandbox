<?php declare(strict_types = 1);

namespace App\Manager;

use App\Communications\SMS\Config\SmsConfig;
use App\Communications\SMS\Model\SMS;
use App\Communications\SMS\SmsCommunicator;
use App\Config\ValidationCodeConfigs;
use App\Config\ValidationCodeLimitsConfig;
use App\Entity\PhoneNumber;
use App\Entity\User;
use App\Entity\ValidationCode\ValidationCodeInterface;
use App\Exception\ApiBadRequestException;
use App\Exception\VerificationLimitException;
use App\Logger\UserActionLogger;
use App\Mailer\MailerInterface;
use App\Manager\Model\SendCodeDiffModel;
use App\Services\TranslatorService\TranslatorInterface;
use App\Utils\RandomNumberInterface;
use App\Utils\Symbols;
use App\Validator\Constraints\ValidationCodeLimits;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ValidationCodeManager implements ValidationCodeManagerInterface
{
    private EntityManagerInterface $entityManager;
    private RandomNumberInterface $randomNumber;
    private PhoneNumberUtil $numberUtil;
    private SmsCommunicator $smsCommunicator;
    private UserActionLogger $userActionLogger;
    private MailerInterface $mailer;
    private TranslatorInterface $translator;
    private SmsConfig $smsConfig;
    private string $isEmailDisabled;
    private ValidationCodeConfigs $validationCodeConfigs;
    private ValidatorInterface $validator;

    public function __construct(
        EntityManagerInterface $entityManager,
        RandomNumberInterface $randomNumber,
        PhoneNumberUtil $numberUtil,
        SmsCommunicator $smsCommunicator,
        UserActionLogger $userActionLogger,
        MailerInterface $mailer,
        TranslatorInterface $translator,
        SmsConfig $smsConfig,
        string $isEmailDisabled,
        ValidationCodeConfigs $validationCodeConfigs,
        ValidatorInterface $validator
    ) {
        $this->entityManager = $entityManager;
        $this->randomNumber = $randomNumber;
        $this->numberUtil = $numberUtil;
        $this->smsCommunicator = $smsCommunicator;
        $this->userActionLogger = $userActionLogger;
        $this->mailer = $mailer;
        $this->isEmailDisabled = $isEmailDisabled;
        $this->translator = $translator;
        $this->smsConfig = $smsConfig;
        $this->validationCodeConfigs = $validationCodeConfigs;
        $this->validator = $validator;
    }

    public function addAttempts(ValidationCodeInterface $phoneCode): ValidationCodeInterface
    {
        $dateNow = new DateTimeImmutable();
        $oldDate = $phoneCode->getAttemptsDate();

        $updDailyLimit = $oldDate && $dateNow->format('DMY') !== $oldDate->format('DMY');
        $updWeeklyLimit = $oldDate && $dateNow->format('WY') !== $oldDate->format('WY');
        $updMonthlyLimit = $oldDate && $dateNow->format('MY') !== $oldDate->format('MY');

        if ($updDailyLimit) {
            $phoneCode->setDailyAttempts(1);
        } else {
            $phoneCode->setDailyAttempts($phoneCode->getDailyAttempts() + 1);
        }

        if ($updWeeklyLimit) {
            $phoneCode->setWeeklyAttempts(1);
        } else {
            $phoneCode->setWeeklyAttempts($phoneCode->getWeeklyAttempts() + 1);
        }

        if ($updMonthlyLimit) {
            $phoneCode->setMonthlyAttempts(1);
        } else {
            $phoneCode->setMonthlyAttempts($phoneCode->getMonthlyAttempts() + 1);
        }

        $phoneCode->setTotalAttempts($phoneCode->getTotalAttempts() + 1);

        $phoneCode->setAttemptsDate(new DateTimeImmutable());

        return $phoneCode;
    }

    public function getCodeState(
        ValidationCodeInterface $phoneCode,
        ValidationCodeLimitsConfig $limits
    ): SendCodeDiffModel {
        $now = new DateTimeImmutable();
        $sendDate = $phoneCode->getSendDate();
        $timeWhenUserIsAbleToSend = $phoneCode->getSendDate()
            ? $sendDate->add(new \DateInterval('PT60S'))
            : $now;

        return new SendCodeDiffModel(
            $now->getTimestamp() >= $timeWhenUserIsAbleToSend->getTimestamp(),
            $timeWhenUserIsAbleToSend->getTimestamp() - $now->getTimestamp(),
            $limits->getOverall() <= $phoneCode->getTotalAttempts()
        );
    }

    public function assertCode(ValidationCodeInterface $validationCode, ValidationCodeLimitsConfig $limits): void
    {
        $codeState = $this->getCodeState($validationCode, $limits);

        if ($validationCode->getSendDate() && !$codeState->isSendCodeEnabled()) {
            throw new AccessDeniedException();
        }

        $validationCodeOwner = $validationCode->getOwner();

        if ($codeState->isLimitReached()) {
            $user = $validationCode->getUser();

            if ($user && $validationCode->shouldBlockOnLimitReached()) {
                $user->setIsBlocked(true);

                $validationCodeOwner->applyOnValidationCodes(function (ValidationCodeInterface $code) {
                    return $this->resetTotalAttempts($code);
                });
                $this->entityManager->persist($user);
                $this->entityManager->flush();

                $this->userActionLogger->info('user '.$user->getId().' blocked, validation limits reached.');
            }

            $exceptionMsg = $this->translator->trans('verification_limit_reached');

            throw new VerificationLimitException($exceptionMsg);
        }
    }

    public function resetTotalAttempts(ValidationCodeInterface $phoneCode): ValidationCodeInterface
    {
        $phoneCode->setTotalAttempts(0);
        $this->entityManager->persist($phoneCode);

        return $phoneCode;
    }

    public function updateCode(ValidationCodeInterface $phoneCode, string $newCode): ValidationCodeInterface
    {
        $phoneCode->setCode($newCode);
        $phoneCode->setFailedAttempts(0);

        $phoneCode->setSendDate(new DateTimeImmutable());

        $phoneCode = $this->addAttempts($phoneCode);
        $this->entityManager->persist($phoneCode);

        $this->entityManager->flush();

        return $phoneCode;
    }

    public function sendSmsValidationCode(ValidationCodeInterface $validationCode, User $user, string $message): array
    {
        $newPhoneCode = $this->randomNumber->generateVerificationCode();
        $phoneNumber = $validationCode->getPhoneNumber();

        $recipient = $phoneNumber->getTemPhoneNumber() ?? $phoneNumber->getPhoneNumber();
        $countryCode = (string)$recipient->getCountryCode();

        $sms = new SMS(
            Symbols::MINTME,
            $this->numberUtil->format($recipient, PhoneNumberFormat::E164),
            $this->translator->trans(
                $message,
                ['%code%' => $newPhoneCode]
            ),
            $countryCode
        );

        $isSmsDisabled = $this->smsConfig->isSmsDisabled();
        $sentBy = null;

        if (!$isSmsDisabled && $this->smsConfig->hasProviders()) {
            $sentBy = $this->smsCommunicator->send($sms, $user);

            if (!$sentBy) {
                throw new ApiBadRequestException($this->translator->trans('api.something_went_wrong'));
            }
        }

        $phoneNumber->setProvider($sentBy);
        $this->updateCode($validationCode, $newPhoneCode);

        return $isSmsDisabled
            ? ['code' => "sms code: $newPhoneCode"]
            : [];
    }

    public function sendMailValidationCode(ValidationCodeInterface $validationCode, User $user, string $subject, ?string $to = null): array
    {
        $newMailCode = $this->randomNumber->generateVerificationCode();

        if (!$this->isEmailDisabled) {
            $this->mailer->sendVerificationCode($user, $newMailCode, $subject, $to);
        }

        $this->updateCode($validationCode, $newMailCode);

        return $this->isEmailDisabled
            ? ['code' => "mail code: $newMailCode"]
            : [];
    }

    public function isSendSMSEnabled(?PhoneNumber $phoneNumber): bool
    {
        if (!$phoneNumber) {
            return true;
        }

        $smsCodeLimits = $this->validationCodeConfigs->getCodeLimits(ValidationCodeConfigs::SMS);
        $smsCodeState = $this->getCodeState($phoneNumber->getSMSCode(), $smsCodeLimits);

        return $smsCodeState->isSendCodeEnabled();
    }

    public function initValidation(
        User $user,
        ValidationCodeInterface $validationCode,
        ValidationCodeLimitsConfig $limits,
        ?string $errorMsg = null
    ): ConstraintViolationListInterface {
        if (!$validationCode->getPhoneNumber()) {
            $validationCode->setPhoneNumber($user->getProfile()->getPhoneNumber());
            $this->entityManager->persist($validationCode);
            $this->entityManager->flush();
        }

        $this->assertCode($validationCode, $limits);

        $validationCodeLimitsConstraint = new ValidationCodeLimits([
            'dailyLimit' => $limits->getDaily(),
            'weeklyLimit' => $limits->getWeekly(),
            'monthlyLimit' => $limits->getMonthly(),
            'messageType' => $errorMsg,
        ]);

        return $this->validator->validate($validationCode, $validationCodeLimitsConstraint);
    }
}
