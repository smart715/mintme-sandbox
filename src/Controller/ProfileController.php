<?php declare(strict_types = 1);

namespace App\Controller;

use App\Activity\ActivityTypes;
use App\Config\ValidationCodeConfigs;
use App\Config\WithdrawalDelaysConfig;
use App\Controller\Traits\ViewOnlyTrait;
use App\Entity\Bonus;
use App\Entity\Profile;
use App\Entity\User;
use App\Events\Activity\BonusEventActivity;
use App\Events\Activity\UserEventActivity;
use App\Events\PhoneChangeEvent;
use App\Events\UserChangeEvents;
use App\Exception\NotFoundProfileException;
use App\Exchange\Balance\Factory\TokensUserOwnsViewFactoryInterface;
use App\Form\PhoneVerificationType;
use App\Form\ProfileType;
use App\Logger\UserActionLogger;
use App\Manager\BlockedUserManagerInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\PhoneNumberManagerInterface;
use App\Manager\ProfileManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Manager\TokenSignupBonusCodeManagerInterface;
use App\Manager\UserTokenManagerInterface;
use App\Manager\ValidationCodeManagerInterface;
use App\Services\TranslatorService\TranslatorInterface;
use App\Validator\Constraints\EditPhoneNumber;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("/profile")
 */
class ProfileController extends Controller
{
    private UserActionLogger $userActionLogger;
    private PhoneNumberUtil $phoneNumberUtil;
    protected SessionInterface $session;
    private TranslatorInterface $translator;
    private PhoneNumberManagerInterface $phoneNumberManager;
    private CryptoManagerInterface $cryptoManager;
    private ProfileManagerInterface $profileManager;
    private EntityManagerInterface $entityManager;
    private BlockedUserManagerInterface $blockedUserManager;
    private ValidationCodeManagerInterface $codeManager;
    private TokenManagerInterface $tokenManager;
    private TokenSignupBonusCodeManagerInterface $tokenSignUpBonusCodeManager;
    private TokensUserOwnsViewFactoryInterface $tokensUserOwnsViewFactory;
    private UserTokenManagerInterface $userTokenManager;
    private MoneyWrapperInterface $moneyWrapper;
    private EventDispatcherInterface $eventDispatcher;
    private WithdrawalDelaysConfig $withdrawalDelaysConfig;

    use ViewOnlyTrait;

    public function __construct(
        NormalizerInterface $normalizer,
        UserActionLogger $userActionLogger,
        PhoneNumberUtil $phoneNumberUtil,
        SessionInterface $session,
        TranslatorInterface $translator,
        PhoneNumberManagerInterface $phoneNumberManager,
        CryptoManagerInterface $cryptoManager,
        ProfileManagerInterface $profileManager,
        EntityManagerInterface $entityManager,
        ValidationCodeManagerInterface $codeManager,
        TokenManagerInterface $tokenManager,
        BlockedUserManagerInterface $blockedUserManager,
        TokenSignupBonusCodeManagerInterface $tokenSignUpBonusCodeManager,
        TokensUserOwnsViewFactoryInterface $tokensUserOwnsViewFactory,
        UserTokenManagerInterface $userTokenManager,
        MoneyWrapperInterface $moneyWrapper,
        EventDispatcherInterface $eventDispatcher,
        WithdrawalDelaysConfig $withdrawalDelaysConfig
    ) {
        parent::__construct($normalizer);
        $this->userActionLogger = $userActionLogger;
        $this->phoneNumberUtil = $phoneNumberUtil;
        $this->session = $session;
        $this->translator = $translator;
        $this->phoneNumberManager = $phoneNumberManager;
        $this->cryptoManager = $cryptoManager;
        $this->profileManager = $profileManager;
        $this->entityManager = $entityManager;
        $this->tokenManager = $tokenManager;
        $this->blockedUserManager = $blockedUserManager;
        $this->codeManager = $codeManager;
        $this->tokenSignUpBonusCodeManager = $tokenSignUpBonusCodeManager;
        $this->tokensUserOwnsViewFactory = $tokensUserOwnsViewFactory;
        $this->userTokenManager = $userTokenManager;
        $this->moneyWrapper = $moneyWrapper;
        $this->eventDispatcher = $eventDispatcher;
        $this->withdrawalDelaysConfig = $withdrawalDelaysConfig;
    }

    private function redirectToProfilePage(bool $edit = true): RedirectResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $profile = $user->getProfile();

        return $this->redirectToRoute('profile-view', [
            'nickname' => $profile->getNickname(),
            'edit' => $edit,
        ]);
    }

    /** @Route("/phone/verify", name="phone_verification") */
    public function phoneConfirmation(
        Request $request,
        ValidatorInterface $validator
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $profile = $user->getProfile();

        if (!$this->isGranted('not-blocked')) {
            $this->addFlash('error', $this->translator->trans('verification_limit_reached'));

            return $this->redirectToProfilePage(false);
        }

        if ($this->isViewOnly()) {
            $this->addFlash('error', 'View only');

            return $this->redirectToProfilePage();
        }

        if (!$profile->getPhoneNumber()) {
            return $this->redirectToProfilePage();
        }

        $phoneToVerify = $profile->getPhoneNumber()->getTemPhoneNumber();

        if (!$phoneToVerify) {
            return $this->redirectToProfilePage();
        }

        if ($this->phoneNumberManager->isPhoneNumberBlacklisted($phoneToVerify)) {
            $this->profileManager->unverifyPhoneNumber($profile);
            $this->addFlash('danger', $this->translator->trans('phone_number.in_use'));

            return $this->redirectToProfilePage();
        }

        $validationCodesConfig = new ValidationCodeConfigs(
            $this->getParameter('phone_codes')
        );

        $smsCodeLimits = $validationCodesConfig->getCodeLimits(ValidationCodeConfigs::SMS);
        $smsCode = $profile->getPhoneNumber()->getSMSCode();

        $mailCodeLimits = $validationCodesConfig->getCodeLimits(ValidationCodeConfigs::EMAIL);
        $mailCode = $profile->getPhoneNumber()->getMailCode();

        $form = $this->createForm(PhoneVerificationType::class, null, [
            'smsFailedAttempts' => $smsCodeLimits->getFailed(),
            'smsLimitReached' => $smsCode->getFailedAttempts() >= $smsCodeLimits->getFailed(),
            'mailFailedAttempts' => $mailCodeLimits->getFailed(),
            'mailLimitReached' => $mailCode->getFailedAttempts() >= $mailCodeLimits->getFailed(),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $errors = $validator->validate($phoneToVerify, new EditPhoneNumber());

            if ($form->isValid() && 0 === count($errors)) {
                $phoneNumber = $profile->getPhoneNumber();

                if (!$phoneNumber->getPhoneNumber()->equals($phoneNumber->getTemPhoneNumber())) {
                    $this->eventDispatcher->dispatch(
                        new PhoneChangeEvent($this->withdrawalDelaysConfig, $profile->getUser()),
                        UserChangeEvents::PHONE_UPDATED
                    );
                }

                $this->eventDispatcher->dispatch(
                    new UserEventActivity($profile->getUser(), ActivityTypes::PHONE_VERIFIED),
                    UserEventActivity::NAME
                );

                $this->profileManager->verifyPhone($profile, $phoneToVerify);

                $this->userActionLogger->info(
                    'Phone number '.$this->phoneNumberUtil->format(
                        $phoneToVerify,
                        PhoneNumberFormat::E164
                    ).' verified.'
                );

                $this->payTokenSignupBonusCode($user);

                return $this->redirectToRoute('profile');
            } elseif (0 !== count($errors)) {
                $this->profileManager->handlePhoneNumberFailedAttempt($profile);
                $form->addError(new FormError($errors[0]->getMessage()));
            }
        }

        $sendCode = false;

        if ($this->session->get('send_verification_code')) {
            $this->session->remove('send_verification_code');
            $sendCode = true;
        }

        return $this->render('pages/phone_verification.html.twig', [
            'form' => $form->createView(),
            'sendCode' => $sendCode,
        ]);
    }

    /** @Route(name="profile", options={"expose"=true}) */
    public function profile(
        Request $request,
        ProfileManagerInterface $profileManager
    ): Response {
        $profile = $profileManager->getProfile($this->getUser());

        if (null !== $profile) {
            return $this->redirectToProfilePage(false);
        }

        /** @var User $user*/
        $user = $this->getUser();

        $profile  = new Profile($user);
        $form = $this->createForm(
            ProfileType::class,
            $profile,
            ['had_phone_number' => (bool)$profile->getPhoneNumber()]
        );
        $form->handleRequest($request);

        if ($this->isViewOnly() || !$form->isSubmitted() || !$form->isValid()) {
            if ($this->isViewOnly()) {
                $this->addFlash('error', 'View only');
            }

            return $this->render('pages/profile.html.twig', [
                'form' =>  $form->createView(),
                'token' => null,
                'profile' => $profile,
                'canEdit' => true,
                'editFormShowFirst' => true,
            ]);
        }

        $this->entityManager->persist($profile);
        $this->entityManager->flush();

        $this->userActionLogger->info('Create profile');

        return $this->redirectToProfilePage(false);
    }

    /** @Route("/{nickname}/{edit}", defaults={"edit"=false}, name="profile-view", options={"expose"=true}) */
    public function profileView(
        Request $request,
        ProfileManagerInterface $profileManager,
        bool $edit,
        string $nickname = ''
    ): Response {
        if (!$nickname) {
            throw new NotFoundProfileException();
        }

        $profile = $profileManager->getProfileByNickname($nickname);

        if (null === $profile) {
            throw new NotFoundProfileException();
        }

        $user = $this->getUser();

        if ($user && $profile->getUser() === $user) {
            $profile->setDisabledAnonymous(true);
        }

        $clonedProfile = clone $profile;
        $form = $this->createForm(
            ProfileType::class,
            $profile,
            ['had_phone_number' => (bool)$profile->getPhoneNumber()]
        );

        try {
            $form->handleRequest($request);
        } catch (\Throwable $exception) {
            $this->addFlash('danger', $this->translator->trans('page.profile.error.nickname.required'));

            return $this->renderProfileViewForm($profile, $clonedProfile, $form, true);
        }

        if ($form->isSubmitted() && $this->isViewOnly()) {
            $this->addFlash('error', 'View only');

            return $this->redirectToProfilePage(false);
        }

        if (!$form->isSubmitted() || !$form->isValid()) {
            $formError = !$edit && $form->getErrors(true)->offsetExists(0)
               ? $form->getErrors(true)->offsetGet(0)
               : null;

            if ($formError instanceof FormError) {
                $this->addFlash('danger', 'Nickname: '.$formError->getMessage());
            }

            return $this->renderProfileViewForm(
                $profile,
                $clonedProfile,
                $form,
                true === $edit ? $edit : (bool)$form->getErrors(true)->count()
            );
        }

        if (null === $profile->getDescription() || '' == $profile->getDescription()) {
            $profile->setNumberOfReminder(0);
            $profile->setNextReminderDate(new \DateTime('+1 month'));
        }

        $phoneNumber = $form->get('phoneNumber')->getData()['phoneNumber'];
        $oldPhoneE164 = $profile->getPhoneNumber() ? $this->phoneNumberUtil->format(
            $profile->getPhoneNumber()->getPhoneNumber(),
            PhoneNumberFormat::E164
        ) : null;

        $newPhoneE164 = $phoneNumber ?
            $this->phoneNumberUtil->format($phoneNumber, PhoneNumberFormat::E164):
            null;

        $phoneChanged = $newPhoneE164 !== $oldPhoneE164;

        $isPhoneNeedVerification = $phoneChanged ||
            ((bool)$oldPhoneE164 && !$profile->getPhoneNumber()->isVerified());

        try {
            $isPhoneNeedVerification ?
                $this->profileManager->changePhone($profile, $phoneNumber):
                $this->profileManager->updateProfile($profile);

            $this->logProfileChanges($profile, $clonedProfile);
        } catch (\Throwable $exception) {
            $this->addFlash('danger', $this->translator->trans('toasted.error.try_again'));
            $this->userActionLogger->error('Change phone error: '.$exception->getMessage());

            return $this->renderProfileViewForm($profile, $clonedProfile, $form, true);
        }

        /** @phpstan-ignore-next-line says it's always true */
        if ($form->isSubmitted() && $form->isValid()) {
            $this->get("security.csrf.token_manager")->refreshToken("form_intention");
        }

        if ($phoneChanged) {
            $this->logProfileChange('phone number changed', $newPhoneE164, $oldPhoneE164);
        }

        if ($isPhoneNeedVerification) {
            if ($this->codeManager->isSendSMSEnabled($profile->getPhoneNumber())) {
                $this->session->set('send_verification_code', true);
            }

            return $this->redirectToRoute('phone_verification');
        }

        return $this->redirectToProfilePage(false);
    }

    private function renderProfileViewForm(
        Profile $profile,
        Profile $clonedProfile,
        FormInterface $form,
        bool $showEdit
    ): Response {
        /** @var User|null $user*/
        $user = $this->getUser();
        $profile->setDisabledAnonymous(true);

        $profileDescription = $profile->getDescription() ?? '';
        $profileDescription = preg_replace('/[\n\r]+/', ' ', $profileDescription);

        $token = $profile->getFirstToken();

        if ($token) {
            $token = $token->isBlocked()
                ? null
                : $token;
        }

        $ownToken = $user
            ? $user->getProfile()->getFirstToken()
            : null;

        $canEdit = $user && $profile->getId() === $user->getProfile()->getId();

        $profileUser = $profile->getUser();

        $isBlockedProfile = $user
            && $this->blockedUserManager->findByBlockedUserAndOwner($user, $profileUser);

        $showBlockUser = $user
            && $profile->getId() !== $user->getProfile()->getId()
            && ($ownToken || $isBlockedProfile);

        $profileUserTokens = $profileUser->getTokens();

        $tokensUserOwns = $profileUserTokens
            ? $this->tokensUserOwnsViewFactory->create(
                $this->userTokenManager->findByUser($profileUser),
                true,
            )
            : [];

        return $this->render('pages/profile.html.twig', [
            'cryptos' => $this->normalize($this->cryptoManager->findAllIndexed('symbol')),
            'token' => $token,
            'profile' => $showEdit ? $clonedProfile : $profile,
            'profileDescription' => substr($profileDescription, 0, 200),
            'form' =>  $form->createView(),
            'canEdit' => $canEdit,
            'editFormShowFirst' => $showEdit,
            'countries' => $canEdit ? Countries::getNames($user->getLocale()) : [],
            'tokensUserOwnsCount' => count($profileUser->getTokens()),
            'phoneEditLimitReached' => $this->profileManager->isPhoneEditLimitReached($profile),
            'showBlockUser' => $showBlockUser,
            'userHasTokens' => null !== $ownToken,
            'isBlockedProfile' => $isBlockedProfile,
            'tokensUserOwns' => $this->normalize($tokensUserOwns),
        ]);
    }

    private function logProfileChanges(Profile $profile, Profile $originalProfile): void
    {
        $properties = [
            'description' => 'Description',
            'firstName' => 'FirstName',
            'lastName' => 'LastName',
            'country' => 'Country',
            'nickname' => 'Nickname',
        ];

        foreach ($properties as $property => $label) {
            $originalValue = $originalProfile->{'get' . ucfirst($property)}();
            $newValue = $profile->{'get' . ucfirst($property)}();

            if ($originalValue !== $newValue) {
                $this->logProfileChange($label, $newValue, $originalValue);
            }
        }

        if ($profile->isAnonymous() !== $originalProfile->isAnonymous()) {
            $this->logProfileChange(
                'Anonymous(status)',
                $profile->isAnonymous() ? 'true' : 'false',
                $originalProfile->isAnonymous() ? 'true' : 'false'
            );
        }
    }

    /**
     * @param string $propertyChanged
     * @param string|bool|null $changedValue
     * @param string|bool|null $originalValue
     * @return void
     */
    private function logProfileChange(string $propertyChanged, $changedValue, $originalValue): void
    {
        $this->userActionLogger->info(
            "|Edit profile| $propertyChanged changed. From: {$originalValue}. To: {$changedValue}"
        );
    }

    private function payTokenSignupBonusCode(User $user): void
    {
        $bonus = $user->getBonus();

        if (!$bonus
            || (Bonus::PENDING_CLAIM_STATUS !== $bonus->getStatus()
                && Bonus::TOKEN_SIGN_UP_TYPE !== $bonus->getType())
        ) {
            return;
        }

        $token = $this->tokenManager->findByName($bonus->getTradableName());

        if (!$token) {
            return;
        }

        $this->tokenSignUpBonusCodeManager->claimTokenSignupBonus(
            $token,
            $user,
            $bonus->getQuantity()
        );

        $bonus->setStatus(Bonus::PAID_STATUS);
        $this->entityManager->persist($bonus);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(new BonusEventActivity($bonus, $token), BonusEventActivity::NAME);
        $this->userActionLogger->info('User '.$user->getId().' claimed token signup bonus', [
            'token' => $token->getId(),
            'bonusId' => $bonus->getId(),
            'quantity' => $bonus->getQuantity(),
        ]);

        $this->addFlash(
            'success',
            $this->translator->trans(
                'api.tokens.token_sign_up_bonus.claim',
                [
                    'amount' => $this->moneyWrapper->format($bonus->getQuantity(), false),
                    'tokenName' => $token->getName(),
                ]
            )
        );
    }
}
