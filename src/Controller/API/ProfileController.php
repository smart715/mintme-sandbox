<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Activity\ActivityTypes;
use App\Communications\SMS\Exception\BlacklistedCodeCountryException;
use App\Config\TFABackupCodesConfigs;
use App\Config\ValidationCodeConfigs;
use App\Config\ValidationCodeLimitsConfig;
use App\Config\WithdrawalDelaysConfig;
use App\Controller\Traits\ViewOnlyTrait;
use App\Entity\User;
use App\Entity\ValidationCode\ValidationCodeInterface;
use App\Events\Activity\UserEventActivity;
use App\Events\PhoneChangeEvent;
use App\Events\UserChangeEvents;
use App\Exception\ApiBadRequestException;
use App\Exception\ApiForbiddenException;
use App\Exception\NotFoundProfileException;
use App\Exception\VerificationLimitException;
use App\Exchange\Balance\Factory\TokensUserOwnsViewFactoryInterface;
use App\Form\PhoneNumberType;
use App\Form\PhoneVerificationType;
use App\Logger\UserActionLogger;
use App\Manager\BlockedUserManagerInterface;
use App\Manager\PhoneNumberManagerInterface;
use App\Manager\ProfileManagerInterface;
use App\Manager\TwoFactorManagerInterface;
use App\Manager\UserManagerInterface;
use App\Manager\UserTokenManagerInterface;
use App\Manager\ValidationCodeManagerInterface;
use App\Services\TranslatorService\TranslatorInterface;
use App\Validator\Constraints\EditPhoneNumber;
use App\Validator\Constraints\ValidationCodeLimits;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Sirprize\PostalCodeValidator\Validator as PostalCodeValidator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Rest\Route("/api/profile")
 */
class ProfileController extends AbstractFOSRestController
{
    public const PHONE_NUMBER_IN_USE_ERROR = 'PHONE_NUMBER_IN_USE';

    private TranslatorInterface $translator;
    private EntityManagerInterface $entityManager;
    private UserActionLogger $userActionLogger;
    private ProfileManagerInterface $profileManager;
    private PhoneNumberManagerInterface $phoneNumberManager;
    private PhoneNumberUtil $phoneNumberUtil;
    private UserManagerInterface $userManager;
    protected SessionInterface $session;
    private ValidatorInterface $validator;
    private ValidationCodeManagerInterface $validationCodeManager;
    private EventDispatcherInterface $eventDispatcher;

    use ViewOnlyTrait;

    public function __construct(
        TranslatorInterface $translator,
        EntityManagerInterface $entityManager,
        UserActionLogger $userActionLogger,
        ProfileManagerInterface $profileManager,
        PhoneNumberManagerInterface $phoneNumberManager,
        PhoneNumberUtil $phoneNumberUtil,
        UserManagerInterface $userManager,
        SessionInterface $session,
        ValidatorInterface $validator,
        ValidationCodeManagerInterface $validationCodeManager,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->translator = $translator;
        $this->entityManager = $entityManager;
        $this->userActionLogger = $userActionLogger;
        $this->profileManager = $profileManager;
        $this->phoneNumberManager = $phoneNumberManager;
        $this->phoneNumberUtil = $phoneNumberUtil;
        $this->userManager = $userManager;
        $this->session = $session;
        $this->validationCodeManager = $validationCodeManager;
        $this->validator = $validator;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @Rest\View()
     * @Rest\Post("/validate-zip-code", name="validate_zip_code", options={"expose"=true})
     * @Rest\RequestParam(name="country", nullable=true)
     */
    public function validateZipCode(ParamFetcherInterface $request): View
    {
        $country = $request->get('country');

        if (null === $country) {
            throw new ApiBadRequestException('Invalid request');
        }

        $validator = new PostalCodeValidator();
        $finalPattern = '';
        $hasPattern = '' === $country
            ? false
            : $validator->hasCountry(mb_strtoupper($country));

        if ($hasPattern) {
            $patterns = $validator->getFormats(mb_strtoupper($country));

            if (0 === count($patterns)) {
                $hasPattern = false;
            } else {
                $search = ['#', '@', ' '];
                $replace = ['\d', '[a-z]', '\s'];

                foreach ($patterns as &$pattern) {
                    $pattern = '(' . str_replace($search, $replace, $pattern) . ')';
                }

                $finalPattern = implode('|', $patterns);

                if (count($patterns) > 1) {
                    $finalPattern = '(' . $finalPattern . ')';
                }
            }
        }

        return $this->view(['hasPattern' => $hasPattern, 'pattern' => $finalPattern], Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/block/{nickname}", name="block_profile", options={"expose"=true})
     * @Rest\RequestParam(name="deleteActions", nullable=false)
     */
    public function block(
        string $nickname,
        BlockedUserManagerInterface $blockedUserManager,
        ParamFetcherInterface $paramFetcher
    ): View {
        /** @var User $owner */
        $owner = $this->getUser();

        if (!$owner->getProfile()->getTokens()) {
            throw new ApiBadRequestException();
        }

        $profile = $this->profileManager->findByNickname($nickname);

        if (!$profile) {
            throw new ApiBadRequestException();
        }

        $userToBlock = $profile->getUser();

        $deleteActions = (bool)$paramFetcher->get('deleteActions');

        $blockedTokenUser = $blockedUserManager->findByBlockedUserAndOwner($owner, $userToBlock);

        if ($blockedTokenUser) {
            throw new ApiBadRequestException();
        }

        $blockedUserManager->blockUser($owner, $userToBlock, $deleteActions);

        return $this->view([], Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/unblock/{nickname}", name="unblock_profile", options={"expose"=true})
     */
    public function unblock(string $nickname, BlockedUserManagerInterface $blockedUserManager): View
    {
        /** @var User $owner */
        $owner = $this->getUser();

        $profile = $this->profileManager->findByNickname($nickname);

        if (!$profile) {
            throw new ApiBadRequestException();
        }

        $userToBlock = $profile->getUser();

        $blockedTokenUser = $blockedUserManager->findByBlockedUserAndOwner($owner, $userToBlock);

        if (!$blockedTokenUser) {
            throw new ApiBadRequestException();
        }

        $blockedUserManager->unblockUser($blockedTokenUser);

        return $this->view([], Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/send-2fa-sms-verification-code", name="send_2fa_sms_verification_code", options={"expose"=true})
     */
    public function sendSmsVerificationCode(
        TwoFactorManagerInterface $twoFactorManager,
        TFABackupCodesConfigs $validationCodeConfigs
    ): View {
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

        /** @var User $user*/
        $user = $this->getUser();

        $googleAuth = $twoFactorManager->getGoogleAuthEntry($user->getId());
        $validationCode = $googleAuth->getSMSCode();

        if (!$validationCode) {
            throw new ApiBadRequestException();
        }

        $errorMsg = '2fa.backup_codes.download.limit.action';
        $messageBody = '2fa.backup_code.download.sms.message';

        return $this->sendSmsValidationCode($validationCode, $validationCodeConfigs, $messageBody, $errorMsg);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/send-phone-verification-code", name="send_phone_verification_code", options={"expose"=true})
     */
    public function sendPhoneVerificationCode(
        ValidationCodeConfigs $validationCodeConfigs
    ): View {
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

        /** @var User $user*/
        $user = $this->getUser();

        $phoneNumber = $user->getProfile()->getPhoneNumber();

        if ($phoneNumber->isVerified() && !$phoneNumber->getTemPhoneNumber()) {
            return $this->view([[]], Response::HTTP_OK);
        }

        $messageBody = 'phone_confirmation.your_verification_code';
        $validationCode = $phoneNumber->getSMSCode();

        return $this->sendSmsValidationCode($validationCode, $validationCodeConfigs, $messageBody);
    }

    /**
     * @Rest\View()
     * @Rest\Post(
     *     "/send-mail-phone-verification-code",
     *     name="send_mail_phone_verification_code",
     *     options={"expose"=true}
     * )
     */
    public function sendMailPhoneVerificationCode(
        ValidationCodeConfigs $validationCodeConfigs
    ): View {
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

        /** @var User $user */
        $user = $this->getUser();

        if (!$this->isGranted('not-blocked')) {
            $exceptionMsg = $this->translator->trans('verification_limit_reached');

            throw new VerificationLimitException($exceptionMsg);
        }

        $this->denyAccessUnlessGranted('not-blocked');

        $phoneNumber = $user->getProfile()->getPhoneNumber();

        if (!$phoneNumber) {
            throw $this->createAccessDeniedException();
        }

        if ($phoneNumber->isVerified() && !$phoneNumber->getTemPhoneNumber()) {
            return $this->view([[]], Response::HTTP_OK);
        }

        $validationCode = $phoneNumber->getMailCode();

        $validationCodeLimits = $validationCodeConfigs->getCodeLimits(ValidationCodeConfigs::SMS);

        $errors = $this->initValidation($user, $validationCode, $validationCodeLimits);

        if (count($errors) > 0) {
            return $this->view(['error' => $errors[0]->getMessage()], Response::HTTP_OK);
        }

        $mailSubject = $this->translator->trans('email.verification_code.subject');

        return $this->view($this->validationCodeManager->sendMailValidationCode($validationCode, $user, $mailSubject));
    }

    /**
     * @Rest\View()
     * @Rest\Get("/can-send-phone-code", name="can_send_phone_code", options={"expose"=true})
     * @return View
     */
    public function isSendCodesDisabled(
        ValidationCodeConfigs $validationCodeConfigs,
        ValidationCodeManagerInterface $validationCodeManager
    ): View {
        /** @var User */
        $user = $this->getUser();
        $userPhoneNumber = $user->getProfile()->getPhoneNumber();

        if (!$userPhoneNumber ||
            !$userPhoneNumber->getSMSCode()
            || !$userPhoneNumber->getMailCode()
        ) {
            throw $this->createAccessDeniedException();
        }

        $smsCode = $userPhoneNumber->getSMSCode();
        $mailCode = $userPhoneNumber->getMailCode();

        $smsCodeLimits = $validationCodeConfigs->getCodeLimits(ValidationCodeConfigs::SMS);
        $mailCodeLimits = $validationCodeConfigs->getCodeLimits(ValidationCodeConfigs::EMAIL);

        return $this->view([
            'sms' => $validationCodeManager->getCodeState($smsCode, $smsCodeLimits),
            'mail' => $validationCodeManager->getCodeState($mailCode, $mailCodeLimits),
        ], Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\GET("/check-phone-in-use", name="check_phone_in_use", options={"expose"=true})
     * @Rest\QueryParam(name="phoneNumber", allowBlank=false)
     */
    public function checkPhoneNumber(ParamFetcherInterface $request): View
    {
        $form = $this->createForm(
            PhoneNumberType::class,
            null,
            ['csrf_protection' => false],
        );

        $form->submit(['phoneNumber' => $request->get('phoneNumber')]);

        if (!$form->isValid()) {
            return $this->view(true, Response::HTTP_OK);
        }

        return $this->view(false, Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/add-phone-number", name="add_phone_number", options={"expose"=true})
     * @Rest\RequestParam(name="phoneNumber", nullable=false)
     */
    public function addPhoneNumber(Request $request): View
    {
        /** @var User $user*/
        $user = $this->getUser();

        $profile = $user->getProfile();

        $form = $this->createForm(PhoneNumberType::class, null, ['csrf_protection' => false]);
        $form->handleRequest($request);

        if (!$form->isValid()) {
            /** @var FormError[] $fieldErrors */
            $fieldErrors = $form->get('phoneNumber')->getErrors();
            $errorMessage = $fieldErrors[0]->getMessage();

            throw new ApiBadRequestException($errorMessage);
        }

        $phoneNumber = $form->get('phoneNumber')->getData();
        $oldPhoneE164 = $profile->getPhoneNumber() ? $this->phoneNumberUtil->format(
            $profile->getPhoneNumber()->getPhoneNumber(),
            PhoneNumberFormat::E164
        ) : null;
        $newPhoneE164 = $this->phoneNumberUtil->format($phoneNumber, PhoneNumberFormat::E164);
        $phoneChanged = $newPhoneE164 !== $oldPhoneE164;

        try {
            $this->profileManager->changePhone($profile, $phoneNumber);
            $this->userActionLogger->info('Edit phone number');

            if ($phoneChanged) {
                $this->userActionLogger->info(
                    'Phone number changed. From: '.$oldPhoneE164. '. To: '.$newPhoneE164.' (not verified yet)'
                );
            }
        } catch (\Throwable $exception) {
            $this->userActionLogger->error('Change phone error: '.$exception->getMessage());

            throw new ApiBadRequestException($this->translator->trans('toasted.error.try_again'));
        }

        return $this->view([], Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/verify-phone-number", name="verify_phone_number", options={"expose"=true})
     * @Rest\RequestParam(name="smsCode", nullable=false)
     * @Rest\RequestParam(name="mailCode", nullable=false)
     */
    public function verifyPhoneNumber(
        ValidationCodeConfigs $validationCodeConfigs,
        ParamFetcherInterface $request,
        ValidatorInterface $validator,
        WithdrawalDelaysConfig $withdrawalDelaysConfig
    ): View {
        /** @var User $user*/
        $user = $this->getUser();
        $profile = $user->getProfile();

        $numberToVerify = $profile->getPhoneNumber()->getTemPhoneNumber();

        if (!$numberToVerify && $profile->getPhoneNumber()->isVerified()) {
            return $this->view([], Response::HTTP_OK);
        }

        if ($this->phoneNumberManager->isPhoneNumberBlacklisted($numberToVerify)) {
            $this->profileManager->unverifyPhoneNumber($profile);

            throw new ApiBadRequestException($this->translator->trans('phone_number.in_use'));
        }

        $smsCodeLimits = $validationCodeConfigs->getCodeLimits(ValidationCodeConfigs::SMS);
        $smsCode = $profile->getPhoneNumber()->getSMSCode();

        $mailCodeLimits = $validationCodeConfigs->getCodeLimits(ValidationCodeConfigs::EMAIL);
        $mailCode = $profile->getPhoneNumber()->getMailCode();

        $form = $this->createForm(PhoneVerificationType::class, null, [
            'csrf_protection' => false,
            'smsFailedAttempts' => $smsCodeLimits->getFailed(),
            'smsLimitReached' => $smsCode->getFailedAttempts() >= $smsCodeLimits->getFailed(),
            'mailFailedAttempts' => $mailCodeLimits->getFailed(),
            'mailLimitReached' => $mailCode->getFailedAttempts() >= $mailCodeLimits->getFailed(),
        ]);
        $form->submit($request->all());

        $errors = $validator->validate($numberToVerify, new EditPhoneNumber());

        if ($form->isValid() && 0 === count($errors)) {
            $phoneNumber = $profile->getPhoneNumber();

            if ($phoneNumber && !$phoneNumber->getPhoneNumber()->equals($phoneNumber->getTemPhoneNumber())) {
                $this->eventDispatcher->dispatch(
                    new PhoneChangeEvent($withdrawalDelaysConfig, $profile->getUser()),
                    UserChangeEvents::PHONE_UPDATED
                );
            }

            $this->eventDispatcher->dispatch(
                new UserEventActivity($profile->getUser(), ActivityTypes::PHONE_VERIFIED),
                UserEventActivity::NAME
            );

            $this->profileManager->verifyPhone($profile, $numberToVerify);

            $this->userActionLogger->info(
                'Phone number ' . $this->phoneNumberUtil->format(
                    $numberToVerify,
                    PhoneNumberFormat::E164
                ).' verified.'
            );

            return $this->view([], Response::HTTP_OK);
        } else {
            $this->profileManager->handlePhoneNumberFailedAttempt($profile);

            /** @var FormError[] */
            $smsCodeErrors = $form->get('smsCode')->getErrors();
            /** @var FormError[] */
            $emailCodeErrors = $form->get('mailCode')->getErrors();

            return $this->view([
                'message' => 0 !== count($errors) ? $errors[0]->getMessage() : null,
                'smsCode' => 0 !== count($smsCodeErrors)
                    ? $smsCodeErrors[0]->getMessage()
                    : null,
                'emailCode' => 0 !== count($emailCodeErrors)
                    ? $emailCodeErrors[0]->getMessage()
                    : null,
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @Rest\View()
     * @Rest\Post("/check-password-duplicate", name="check_password_duplicate", options={"expose"=true})
     * @Rest\RequestParam(name="password", nullable=false)
     * @Rest\RequestParam(name="token", nullable=false)
     */
    public function checkUserPasswordIsDuplicate(
        ParamFetcherInterface $request,
        UserPasswordEncoderInterface $encoder
    ): View {
        /** @var User|null $user */
        $user = $request->get('token')
            ? $this->userManager->findUserByConfirmationToken($request->get('token'))
            : $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        return $this->view(
            ['isDuplicate' => $encoder->isPasswordValid($user, $request->get('password'))],
            Response::HTTP_OK
        );
    }

    private function sendSmsValidationCode(
        ?ValidationCodeInterface $validationCode,
        ValidationCodeConfigs $validationCodeConfigs,
        string $msgBody,
        ?string $errorMsg = null
    ): View {
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

        /** @var User $user */
        $user = $this->getUser();

        if (!$this->isGranted('not-blocked')) {
            $exceptionMsg = $this->translator->trans('verification_limit_reached');

            throw new VerificationLimitException($exceptionMsg);
        }

        $phoneNumber = $user->getProfile()->getPhoneNumber();

        if (!$phoneNumber) {
            throw $this->createAccessDeniedException();
        }

        if (!$validationCode) {
            throw new ApiBadRequestException();
        }

        $validationCodeLimits = $validationCodeConfigs->getCodeLimits(ValidationCodeConfigs::SMS);

        $errors = $this->initValidation($user, $validationCode, $validationCodeLimits, $errorMsg);

        if (count($errors) > 0) {
            return $this->view(['error' => $errors[0]->getMessage()], Response::HTTP_OK);
        }

        try {
            return $this->view($this->validationCodeManager->sendSmsValidationCode($validationCode, $user, $msgBody));
        } catch (\Throwable $exception) {
            if ($exception instanceof BlacklistedCodeCountryException) {
                return $this->view(['error' => $this->translator->trans('sms_code_sent.something_went_wrong')]);
            }

            throw $exception;
        }
    }

    private function initValidation(
        User $user,
        ValidationCodeInterface $validationCode,
        ValidationCodeLimitsConfig $limits,
        ?string $errorMsg = null
    ): ConstraintViolationListInterface {
        $phoneCode = $validationCode;

        if (!$phoneCode->getPhoneNumber()) {
            $phoneCode->setPhoneNumber($user->getProfile()->getPhoneNumber());
            $this->entityManager->persist($phoneCode);
            $this->entityManager->flush();
        }

        $this->validationCodeManager->assertCode($phoneCode, $limits);

        $validationCodeLimitsConstraint = new ValidationCodeLimits([
            'dailyLimit' => $limits->getDaily(),
            'weeklyLimit' => $limits->getWeekly(),
            'monthlyLimit' => $limits->getMonthly(),
            'messageType' => $errorMsg,
        ]);

        return $this->validator->validate($phoneCode, $validationCodeLimitsConstraint);
    }
}
