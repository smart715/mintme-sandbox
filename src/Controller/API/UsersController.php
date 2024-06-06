<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Config\EmailValidationCodeConfigs;
use App\Config\UserLimitsConfig;
use App\Config\ValidationCodeConfigs;
use App\Config\ValidationCodeLimitsConfig;
use App\Config\WithdrawalDelaysConfig;
use App\Controller\Traits\ViewOnlyTrait;
use App\Controller\TwoFactorAuthenticatedInterface;
use App\Entity\Api\Client;
use App\Entity\ApiKey;
use App\Entity\User;
use App\Entity\ValidationCode\ValidationCodeInterface;
use App\Events\EmailChangeEvent;
use App\Events\PasswordChangeEvent;
use App\Events\UserChangeEvents;
use App\Exception\ApiBadRequestException;
use App\Exception\ApiForbiddenException;
use App\Exception\ApiNotFoundException;
use App\Form\ChangeMailVerificationType;
use App\Form\ChangePasswordType;
use App\Form\TFASmsVerificationType;
use App\Logger\UserActionLogger;
use App\Manager\DeployNotificationManagerInterface;
use App\Manager\TFACodesManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Manager\TwoFactorManagerInterface;
use App\Manager\UserManagerInterface;
use App\Manager\ValidationCodeManagerInterface;
use App\Services\TranslatorService\TranslatorInterface;
use App\Validator\Constraints\BackupCodesDownloadLimits;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use FOS\OAuthServerBundle\Entity\ClientManager;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Rest\Route("/api/users")
 */
class UsersController extends AbstractFOSRestController implements TwoFactorAuthenticatedInterface
{
    protected UserManagerInterface $userManager;
    private UserActionLogger $userActionLogger;
    private EventDispatcherInterface $eventDispatcher;
    private ClientManager $clientManager;
    private TranslatorInterface $translations;
    private UserLimitsConfig $userLimitsConfig;
    private TFACodesManagerInterface $tfaCodesManager;
    private ValidationCodeManagerInterface $validationCodeManager;
    private EntityManagerInterface $entityManager;
    protected SessionInterface $session;
    private WithdrawalDelaysConfig $withdrawalDelaysConfig;
    private TokenManagerInterface $tokenManager;

    use ViewOnlyTrait;

    public function __construct(
        UserManagerInterface $userManager,
        UserActionLogger $userActionLogger,
        ClientManager $clientManager,
        EventDispatcherInterface $eventDispatcher,
        TFACodesManagerInterface $tfaCodesManager,
        TranslatorInterface $translations,
        UserLimitsConfig $userLimitsConfig,
        ValidationCodeManagerInterface $validationCodeManager,
        EntityManagerInterface $entityManager,
        SessionInterface $session,
        WithdrawalDelaysConfig $withdrawalDelaysConfig,
        TokenManagerInterface $tokenManager
    ) {
        $this->userManager = $userManager;
        $this->userActionLogger = $userActionLogger;
        $this->clientManager = $clientManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->translations = $translations;
        $this->userLimitsConfig = $userLimitsConfig;
        $this->tfaCodesManager = $tfaCodesManager;
        $this->validationCodeManager = $validationCodeManager;
        $this->entityManager = $entityManager;
        $this->session = $session;
        $this->withdrawalDelaysConfig = $withdrawalDelaysConfig;
        $this->tokenManager = $tokenManager;
    }

    /**
     * @Rest\View()
     * @Rest\Get("/keys", name="get_keys", options={"expose"=true})
     */
    public function getApiKeys(): ApiKey
    {
        $curUser = $this->getUser();
        $keys = null;

        if ($curUser instanceof User) {
            /** @var User $curUser */
            $keys = $curUser->getApiKey();
        }

        if (!$keys) {
            throw new ApiNotFoundException($this->translations->trans('api.user.no_keys_attached'));
        }

        return $keys;
    }

    /**
     * @Rest\View()
     * @Rest\Post("/download-tfa-backupcodes", name="download_two_factor_backup_code", options={"expose"=true})
     * @Rest\RequestParam(name="smsCode", nullable=false)
     * @Rest\RequestParam(name="regenerate", nullable=true)
     */
    public function downloadTwoFactorBackupCode(
        ValidationCodeConfigs $validationCodeConfigs,
        TwoFactorManagerInterface $twoFactorManager,
        ParamFetcherInterface $request,
        ValidatorInterface $validator
    ): View {
        /** @var User $user */
        $user = $this->getUser();

        $googleAuthEntry = $twoFactorManager->getGoogleAuthEntry($user->getId());

        $smsCodeLimits = $validationCodeConfigs->getCodeLimits(ValidationCodeConfigs::SMS);
        $smsCode = $googleAuthEntry->getSMSCode();

        $form = $this->createForm(TFASmsVerificationType::class, null, [
            'csrf_protection' => false,
            'smsFailedAttempts' => $smsCodeLimits->getFailed(),
            'smsLimitReached' => $smsCode->getFailedAttempts() >= $smsCodeLimits->getFailed(),
        ]);

        $form->submit(['smsCode' => $request->get('smsCode')]);

        $errors = $validator->validate($user, new BackupCodesDownloadLimits());

        if ($form->isValid() && 0 === count($errors)) {
            $backupCodes =  $this->tfaCodesManager->generateBackupCodesFile($user, $request->get('regenerate'));
            $this->tfaCodesManager->handleDownloadCodeSuccess($user);

            return $this->view($backupCodes, Response::HTTP_OK);
        }

        /** @var FormError[] $smsCodeErrors */
        $smsCodeErrors = $form->get('smsCode')->getErrors();

        return $this->view([
            'message' => 0 !== count($errors) ? $errors[0]->getMessage() : null,
            'smsCode' => 0 !== count($smsCodeErrors)
                ? $smsCodeErrors[0]->getMessage()
                : null,
            ], Response::HTTP_BAD_REQUEST);
    }

    /**
     * @Rest\View(statusCode=201)
     * @Rest\Post("/keys", name="post_keys", options={"expose"=true})
     * @return ApiKey|null
     */
    public function createApiKeys(): ?ApiKey
    {
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

        $user = $this->getUser();

        if (!$user) {
            throw new ApiBadRequestException($this->translations->trans('api.tokens.internal_error'));
        }

        /** @var User $user */
        if ($user->getApiKey()) {
            throw new ApiBadRequestException($this->translations->trans('api.user.key_already_created'));
        }

        $keys = ApiKey::fromNewUser($user);

        $this->getEm()->persist($keys);
        $this->getEm()->flush();
        $this->userActionLogger->info('Created API keys');

        return $keys;
    }

    /**
     * @Rest\View(statusCode=203)
     * @Rest\Delete("/keys", name="delete_keys", options={"expose"=true})
     */
    public function invalidateApiKeys(): void
    {
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

        /** @var User $user*/
        $user = $this->getUser();

        $keys = $user->getApiKey();

        if (!$keys) {
            throw new ApiNotFoundException($this->translations->trans('api.user.no_keys_attached'));
        }

        $this->getEm()->remove($keys);
        $this->getEm()->flush();
        $this->userActionLogger->info('Deleted API keys');
    }

    /**
     * @Rest\View(statusCode=201)
     * @Rest\Post("/clients", name="post_client", options={"expose"=true})
     * @Rest\RequestParam(name="code", nullable=false)
     */
    public function createApiClient(TranslatorInterface $translator, ParamFetcherInterface $request): array
    {
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

        if (!$this->isGranted('2fa-login', $request->get('code'))) {
            throw new UnauthorizedHttpException('2fa', $translator->trans('page.settings_invalid_2fa'));
        }

        /** @var User $user*/
        $user = $this->getUser();
        $oauthKeysLimit = $this->userLimitsConfig->getMaxClientsLimit();

        if (!$this->isGranted('create-oauth')) {
            throw new ApiForbiddenException(
                $this->translations->trans(
                    'api.oauth_keys_limit',
                    ['%limit%' => $oauthKeysLimit]
                )
            );
        }

        /** @var Client $client */
        $client = $this->clientManager->createClient();
        $client->setAllowedGrantTypes(['client_credentials']);
        $client->setUser($user);
        $this->clientManager->updateClient($client);

        return ['id' => $client->getPublicId(), 'secret' => $client->getSecret()];
    }

    /**
     * @Rest\View(statusCode=203)
     * @Rest\Delete(
     *     "/clients/{id}",
     *     name="delete_client",
     *     requirements={"id"="^\d+_[a-zA-Z0-9]+$"},
     *     options={"expose"=true}
     * )
     * @param string $id
     * @return bool
     * @throws ApiNotFoundException
     */
    public function deleteApiClient(string $id): bool
    {
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

        $ids = explode('_', $id);

        $user = $this->getUser();
        $client = $this->clientManager->findClientBy(['user' => $user, 'randomId' => $ids[1], 'id' => $ids[0]]);

        if (!($client instanceof Client)) {
            throw new ApiNotFoundException($this->translations->trans('api.user.no_clients_attached'));
        }

        $this->clientManager->deleteClient($client);
        $this->userActionLogger->info('Deleted API Client');

        return true;
    }

    /**
     * @Rest\View()
     * @Rest\Patch(
     *      "/settings/update-password",
     *      name="update-password",
     *      options={"expose"=true}
     * )
     * @Rest\RequestParam(name="currentPassword", nullable=false)
     * @Rest\RequestParam(name="plainPassword", nullable=false)
     * @Rest\RequestParam(name="code", nullable=true)
     * @throws ApiBadRequestException
     */
    public function changePassOnTwoFaActive(Request $request): Response
    {
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

        if (!$this->isGranted('2fa-login', $request->get('code'))) {
            throw new UnauthorizedHttpException('2fa', $this->translations->trans('Invalid 2FA code'));
        }

        /** @var User|null $user*/
        $user = $this->getUser();

        if (!$user) {
            throw new ApiBadRequestException($this->translations->trans('api.tokens.internal_error'));
        }

        $errorOnPasswordForm = $this->checkStoredUserPassword($request, $user);

        if ($errorOnPasswordForm) {
            throw new ApiBadRequestException($errorOnPasswordForm);
        }

        $this->userManager->updatePassword($user);
        $this->userManager->updateUser($user);
        $response = new Response(Response::HTTP_OK);

        $event = new FilterUserResponseEvent($user, $request, $response);

        /** @psalm-suppress TooManyArguments */
        $this->eventDispatcher->dispatch(
            $event,
            UserChangeEvents::PASSWORD_UPDATED_MSG
        );

        $this->eventDispatcher->dispatch(
            new PasswordChangeEvent($this->withdrawalDelaysConfig, $user),
            UserChangeEvents::PASSWORD_UPDATED
        );

        return $response;
    }

    /**
     * @Rest\View()
     * @Rest\Patch(
     *      "/settings/check-user-password",
     *      name="check-user-password",
     *      options={"expose"=true}
     * )
     * @Rest\RequestParam(name="currentPassword", nullable=false)
     * @Rest\RequestParam(name="plainPassword", nullable=false)
     * @throws ApiBadRequestException
     */
    public function checkUserPassword(Request $request): Response
    {
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

        /** @var User|null $user*/
        $user = $this->getUser();

        if (!$user) {
            throw new ApiBadRequestException($this->translations->trans('api.tokens.internal_error'));
        }

        $errorOnPasswordForm = $this->checkStoredUserPassword($request, $user);

        if ($errorOnPasswordForm) {
            throw new ApiBadRequestException($errorOnPasswordForm);
        }

        return new Response(Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/change-email", name="change_email", options={"expose"=true})
     * @Rest\RequestParam(name="newEmail", nullable=false)
    */
    public function userChangeEmailRequest(
        Request $request
    ): View {
        /** @var string $newEmail */
        $newEmail = $request->get('newEmail');

        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            throw new ApiBadRequestException($this->translations->trans('change_email.error.email.type'));
        }

        $isEmailUsed = !!$this->userManager->findUserByEmail($newEmail);

        if ($isEmailUsed) {
            throw new ApiBadRequestException($this->translations->trans('email.in_use'));
        }

        /** @var User $user */
        $user = $this->getUser();

        try {
            $this->userManager->changeEmail($user, $newEmail);
            $this->entityManager->flush();

            $this->userActionLogger->info(
                'Email changed. From: '.$user->getEmail(). '. To: '.$newEmail.' (not verified yet)'
            );
        } catch (\Throwable $exception) {
            $this->userActionLogger->error('Change email error: ' . $exception->getMessage());

            throw new ApiBadRequestException($this->translations->trans('toasted.error.try_again'));
        }

        $isTwoFactor = $user->isGoogleAuthenticatorEnabled();

        return $this->view(['isTwoFactor' => $isTwoFactor], Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Put("/new-email/verify", name="new_email_verification", options={"expose"=true})
     * @Rest\RequestParam(name="currentEmailCode", nullable=false)
     * @Rest\RequestParam(name="newEmailCode", nullable=false)
     * @Rest\RequestParam(name="tfaCode", nullable=false)
     */
    public function verifyNewEmail(
        EmailValidationCodeConfigs $validationCodeConfigs,
        Request $request
    ): View {
        /** @var User $user */
        $user = $this->getUser();

        $userChangeEmailRequest = $this->userManager->getUserChangeEmailRequest($user);

        if (!$userChangeEmailRequest) {
            throw $this->createAccessDeniedException();
        }

        $currentMailCodeLimits = $validationCodeConfigs->getCodeLimits(EmailValidationCodeConfigs::CURRENT_EMAIL);
        $currentMailCode = $userChangeEmailRequest->getCurrentEmailCode();

        $newMailCodeLimits = $validationCodeConfigs->getCodeLimits(EmailValidationCodeConfigs::NEW_EMAIL);
        $newMailCode = $userChangeEmailRequest->getNewEmailCode();
        $isTwoFactor = $user->isGoogleAuthenticatorEnabled();

        $form = $this->createForm(ChangeMailVerificationType::class, null, [
            'csrf_protection' => false,
            'currentMailFailedAttempts' => $currentMailCodeLimits->getFailed(),
            'currentMailLimitReached' => $currentMailCode->getFailedAttempts() >= $currentMailCodeLimits->getFailed(),
            'newMailFailedAttempts' => $newMailCodeLimits->getFailed(),
            'newMailLimitReached' => $newMailCode->getFailedAttempts() >= $newMailCodeLimits->getFailed(),
            'allow_extra_fields' => true,
        ]);

        $form->submit([
            'currentEmailCode' => $request->get('currentEmailCode'),
            'newEmailCode' => $request->get('newEmailCode'),
            'tfaCode' =>  $request->get('tfaCode'),
        ]);

        if (!$form->isValid()) {
            /** @var FormError[] */
            $currentEmailCodeErrors = $form->get('currentEmailCode')->getErrors();
            /** @var FormError[] */
            $newEmailCodeErrors = $form->get('newEmailCode')->getErrors();
            /** @var FormError[] */
            $tfaCodeErrors = $isTwoFactor
                ? $form->get('tfaCode')->getErrors()
                : [];

            return $this->view([
                'currentEmailCode' => 0 !== count($currentEmailCodeErrors)
                    ? $currentEmailCodeErrors[0]->getMessage()
                    : null,
                'newEmailCode' => 0 !== count($newEmailCodeErrors)
                    ? $newEmailCodeErrors[0]->getMessage()
                    : null,
                'tfaCode' => 0 !== count($tfaCodeErrors)
                    ? $tfaCodeErrors[0]->getMessage()
                    : null,
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!$this->userManager->verifyNewEmail($user)) {
            throw new ApiBadRequestException($this->translations->trans('api.something_went_wrong'));
        }

        $this->entityManager->flush();

        $this->userActionLogger->info(
            "Email change {$userChangeEmailRequest->getOldEmail()} to {$user->getEmail()}"
        );

        $this->eventDispatcher->dispatch(
            new EmailChangeEvent($this->withdrawalDelaysConfig, $user),
            UserChangeEvents::EMAIL_UPDATED
        );

        return $this->view([], Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/send-current-email-verification-code",
     *      name="send_current_email_verification_code",
     *      options={"expose"=true}
     * )
     */
    public function sendCurrentEmailVerificationCode(
        EmailValidationCodeConfigs $validationCodeConfigs
    ): View {
        /** @var User $user */
        $user = $this->getUser();

        $userChangeEmailRequest = $this->userManager->getUserChangeEmailRequest($user);

        if (!$userChangeEmailRequest) {
            throw $this->createAccessDeniedException();
        }

        $validationCode = $userChangeEmailRequest->getCurrentEmailCode();

        if (!$validationCode) {
            throw $this->createAccessDeniedException();
        }

        $validationCodeLimits = $validationCodeConfigs->getCodeLimits(EmailValidationCodeConfigs::CURRENT_EMAIL);
        $to = $userChangeEmailRequest->getOldEmail();

        return $this->sendChangeEmailMailCode($user, $validationCode, $validationCodeLimits, $to);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/send-current-email-sms-verification-code",
     *      name="send_current_email_sms_verification_code",
     *      options={"expose"=true}
     * )
     */
    public function sendCurrentEmailSmsVerificationCode(
        EmailValidationCodeConfigs $validationCodeConfigs
    ): View {
        /** @var User $user*/
        $user = $this->getUser();

        $userChangeEmailRequest = $this->userManager->getUserChangeEmailRequest($user);

        if (!$userChangeEmailRequest) {
            throw $this->createAccessDeniedException();
        }

        $phoneNumber = $user->getProfile()->getPhoneNumber();

        if (!$phoneNumber) {
            throw $this->createAccessDeniedException();
        }

        $validationCode = $userChangeEmailRequest->getCurrentEmailCode();

        if (!$validationCode) {
            throw $this->createAccessDeniedException();
        }

        $validationCodeLimits = $validationCodeConfigs->getCodeLimits(EmailValidationCodeConfigs::CURRENT_EMAIL);

        return $this->sendChangeEmailSmsCode($user, $validationCode, $validationCodeLimits);
    }

    /**
     * @Rest\View()
     * @Rest\Post(
     *     "/send-new-email-verification-code",
     *     name="send_new_email_verification_code",
     *     options={"expose"=true}
     * )
     */
    public function sendNewMailVerificationCode(
        EmailValidationCodeConfigs $validationCodeConfigs
    ): View {
        /** @var User $user */
        $user = $this->getUser();

        $userChangeEmailRequest = $this->userManager->getUserChangeEmailRequest($user);

        if (!$userChangeEmailRequest) {
            throw $this->createAccessDeniedException();
        }

        $validationCode = $userChangeEmailRequest->getNewEmailCode();

        if (!$validationCode) {
            throw $this->createAccessDeniedException();
        }

        $validationCodeLimits = $validationCodeConfigs->getCodeLimits(EmailValidationCodeConfigs::NEW_EMAIL);
        $to = $userChangeEmailRequest->getNewEmail();

        return $this->sendChangeEmailMailCode($user, $validationCode, $validationCodeLimits, $to);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/{tokenName}/send-deploy-notification",
     *      name="send_deploy_notification",
     *      options={"expose"=true}
     * )
     */
    public function sendDeployNotification(
        string $tokenName,
        DeployNotificationManagerInterface $deployNotificationManager
    ): View {
        /** @var User $user */
        $user = $this->getUser();

        $token = $this->tokenManager->findByName($tokenName);

        if (!$token || $token->isDeployed() || $deployNotificationManager->alreadyNotified($user, $token)) {
            throw new ApiBadRequestException();
        }

        try {
            $deployNotificationManager->createAndNotify($user, $token);
        } catch (\Throwable $err) {
            $this->userActionLogger->error('Failed to create and notify user about deploy', [
                'user' => $user->getEmail(),
                'token' => $token->getName(),
                'message' => $err->getMessage(),
            ]);

            return $this->view([
                'error' => $this->translations->trans('api.something_went_wrong'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->view([], Response::HTTP_OK);
    }

    private function checkStoredUserPassword(Request $request, User $user): ?string
    {
        $changePasswordData = $request->request->all();
        $passwordForm = $this->createForm(ChangePasswordType::class, $user, [
            'csrf_protection' => false,
            'allow_extra_fields' => true,
        ]);

        $passwordForm->submit(array_filter($changePasswordData, function ($value) {
            return null !== $value;
        }), false);

        if (!$passwordForm->isValid()) {
            foreach ($passwordForm->all() as $childForm) {
                /** @var FormError[] $fieldErrors */
                $fieldErrors = $passwordForm->get($childForm->getName())->getErrors();

                if (count($fieldErrors) > 0) {
                    return $fieldErrors[0]->getMessage();
                }
            }

            return $this->translations->trans('api.tokens.invalid_argument');
        }

        return null;
    }

    private function getEm(): ObjectManager
    {
        return $this->getDoctrine()->getManager();
    }

    /**
     * @return UserInterface|object|null
     */
    protected function getUser()
    {
        return parent::getUser();
    }

    private function sendChangeEmailMailCode(
        User $user,
        ValidationCodeInterface $validationCode,
        ValidationCodeLimitsConfig $validationCodeLimits,
        string $to
    ): View {
        $errors = $this->validationCodeManager->initValidation($user, $validationCode, $validationCodeLimits, '');

        if (count($errors) > 0) {
            return $this->view(['error' => $errors[0]->getMessage()], Response::HTTP_OK);
        }

        $subject = $this->translations->trans('email.change_email.verification_code.subject');

        return $this->view($this->validationCodeManager->sendMailValidationCode($validationCode, $user, $subject, $to));
    }

    private function sendChangeEmailSmsCode(
        User $user,
        ValidationCodeInterface $validationCode,
        ValidationCodeLimitsConfig $validationCodeLimits
    ): View {
        $errors = $this->validationCodeManager->initValidation($user, $validationCode, $validationCodeLimits, '');

        if (count($errors) > 0) {
            return $this->view(['error' => $errors[0]->getMessage()], Response::HTTP_OK);
        }

        $messageBody = 'change_email.sms.message';

        return $this->view($this->validationCodeManager->sendSmsValidationCode($validationCode, $user, $messageBody));
    }
}
