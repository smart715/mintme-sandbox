<?php declare(strict_types = 1);

namespace App\Controller;

use App\Entity\PhoneNumber;
use App\Entity\Profile;
use App\Entity\User;
use App\Exception\NotFoundProfileException;
use App\Form\PhoneVerificationType;
use App\Form\ProfileType;
use App\Logger\UserActionLogger;
use App\Manager\ProfileManagerInterface;
use App\Manager\UserManagerInterface;
use App\Utils\Converter\String\BbcodeMetaTagsStringStrategy;
use App\Utils\Converter\String\StringConverter;
use App\Validator\Constraints\EditPhoneNumber;
use DateTimeImmutable;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("/profile")
 */
class ProfileController extends Controller
{
    private UserActionLogger $userActionLogger;
    private PhoneNumberUtil $phoneNumberUtil;
    private UserManagerInterface $userManager;
    private TokenStorageInterface $tokenStorage;
    private SessionInterface $session;

    public function __construct(
        NormalizerInterface $normalizer,
        UserActionLogger $userActionLogger,
        PhoneNumberUtil $phoneNumberUtil,
        UserManagerInterface $userManager,
        TokenStorageInterface $tokenStorage,
        SessionInterface $session
    ) {
        parent::__construct($normalizer);
        $this->userActionLogger = $userActionLogger;
        $this->phoneNumberUtil = $phoneNumberUtil;
        $this->userManager = $userManager;
        $this->tokenStorage = $tokenStorage;
        $this->session = $session;
    }

    /** @Route("/phone/verify", name="phone_verification") */
    public function phoneConfirmation(Request $request, ValidatorInterface $validator): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $profile = $user->getProfile();

        if (!$profile->getPhoneNumber()) {
            return $this->redirectToRoute('profile-view', [
                'nickname' => $profile->getNickname(),
                'edit' => true,
            ]);
        }

        $form = $this->createForm(PhoneVerificationType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $entityManager = $this->getDoctrine()->getManager();

            $errors = $validator->validate($profile->getPhoneNumber()->getPhoneNumber(), new EditPhoneNumber());

            if ($form->isValid() && 0 === count($errors)) {
                $profile->getPhoneNumber()->setVerified(true);
                $profile->getPhoneNumber()->setVerificationCode(null);
                $profile->getPhoneNumber()->setEditDate(new DateTimeImmutable());
                $profile->getPhoneNumber()->setEditAttempts(0);
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($profile);
                $entityManager->flush();
                $user->removeRole(User::ROLE_SEMI_AUTHENTICATED);
                $user->addRole(User::ROLE_AUTHENTICATED);
                $this->userManager->updateUser($user);
                $newToken = new PostAuthenticationGuardToken(
                    $user,
                    'authenticate',
                    [User::ROLE_AUTHENTICATED, User::ROLE_DEFAULT]
                );
                $this->tokenStorage->setToken($newToken);

                $this->userActionLogger->info(
                    'Phone number '.$this->phoneNumberUtil->format(
                        $profile->getPhoneNumber()->getPhoneNumber(),
                        PhoneNumberFormat::E164
                    ).' verified.'
                );

                return $this->redirectToRoute('profile');
            } elseif (0 !== count($errors)) {
                $form->get('verificationCode')->addError(new FormError($errors[0]->getMessage()));
            }

            $profile->getPhoneNumber()->incrementFailedAttempts();
            $entityManager->persist($profile);
            $entityManager->flush();
        }

        $failedAttempts = $totalLimit = $this->getParameter('adding_phone_attempts_limit')['failed'];
        $sendCode = false;

        if ($this->session->get('send_verification_code')) {
            $this->session->remove('send_verification_code');
            $sendCode = true;
        }

        return $this->render('pages/phone_verification.html.twig', [
            'failedAttempts' => $failedAttempts,
            'limitReached' => $profile->getPhoneNumber()->getFailedAttempts() >= $failedAttempts,
            'form' => $form->createView(),
            'sendCode' => $sendCode,
        ]);
    }

    /** @Route("/{nickname}/{edit}", defaults={"edit"=false}, name="profile-view", options={"expose"=true}) */
    public function profileView(
        Request $request,
        ProfileManagerInterface $profileManager,
        string $nickname,
        bool $edit
    ): Response {
        $profile = $profileManager->getProfileByNickname($nickname);

        if (null === $profile) {
            throw new NotFoundProfileException();
        }

        $user = $this->getUser();

        if ($user && $profile->getUser() === $user) {
            $profile->setDisabledAnonymous(true);
        }

        $clonedProfile = clone $profile;
        $form = $this->createForm(ProfileType::class, $profile);

        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
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

        $phoneChanged = false;
        $verifyPhone = false;

        if ($phoneNumber) {
            $phoneChanged = $newPhoneE164 !== $oldPhoneE164;

            $verifyPhone = $phoneChanged || !$profile->getPhoneNumber()->isVerified();

            if ($verifyPhone) {
                if (!$oldPhoneE164) {
                    $profile->setPhoneNumber(new PhoneNumber());
                    $profile->getPhoneNumber()->setProfile($profile);
                }

                $profile->getPhoneNumber()->setProfile($profile);
                $profile->getPhoneNumber()->setPhoneNumber($phoneNumber);
                $profile->getPhoneNumber()->setVerified(false);
                $profile->getPhoneNumber()->setVerificationCode(null);
            }
        } elseif (!$oldPhoneE164) {
            $profile->setPhoneNumber(null);
            $phoneChanged = true;
        }

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($profile);

        try {
            $entityManager->flush();
        } catch (\Throwable $exception) {
            $this->addFlash('danger', 'an error occurred please try again!');

            return $this->renderProfileViewForm($profile, $clonedProfile, $form, true);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $this->get("security.csrf.token_manager")->refreshToken("form_intention");
        }

        $this->userActionLogger->info('Edit profile');

        if ($phoneChanged) {
            if ($newPhoneE164) {
                $this->userActionLogger->info(
                    'Phone number changed. From: '.$oldPhoneE164. '. To: '.$newPhoneE164.' (not verified yet)'
                );
            } else {
                $this->userActionLogger->info(
                    'Phone number changed. From: '.$oldPhoneE164. '. To: NULL.'
                );
            }
        }

        if ($verifyPhone) {
            $this->session->set('send_verification_code', true);

            return $this->redirectToRoute('phone_verification');
        }

        return $this->redirectToRoute('profile-view', [ 'nickname' => $profile->getNickname() ]);
    }

    /** @Route(name="profile", options={"expose"=true}) */
    public function profile(
        Request $request,
        ProfileManagerInterface $profileManager
    ): Response {
        $profile = $profileManager->getProfile($this->getUser());

        if (null !== $profile) {
            return $this->redirectToRoute('profile-view', [ 'nickname' => $profile->getNickname() ]);
        }

        /** @var User $user*/
        $user = $this->getUser();

        $profile  = new Profile($user);
        $form = $this->createForm(ProfileType::class, $profile);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render('pages/profile.html.twig', [
                'form' =>  $form->createView(),
                'token' => null,
                'profile' => $profile,
                'canEdit' => true,
                'editFormShowFirst' => true,
            ]);
        }

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($profile);
        $entityManager->flush();

        $this->userActionLogger->info('Create profile');

        return $this->redirectToRoute('profile-view', [ 'nickname' => $profile->getNickname() ]);
    }

    private function renderProfileViewForm(
        Profile $profile,
        Profile $clonedProfile,
        FormInterface $form,
        bool $showEdit
    ): Response {
        /** @var User $user*/
        $user = $this->getUser();
        $profile->setDisabledAnonymous(true);

        $profileDescription = $profile->getDescription() ?? '';
        $profileDescription = (new StringConverter(new BbcodeMetaTagsStringStrategy()))->convert($profileDescription);
        $profileDescription = preg_replace(
            '/\[\/?(?:b|i|u|s|ul|ol|li|p|s|url|img|h1|h2|h3|h4|h5|h6)*?.*?\]/',
            '\2',
            $profileDescription
        ) ?? '';
        $profileDescription = preg_replace('/[\n\r]+/', ' ', $profileDescription);

        $token = $profile->getFirstToken();

        if ($token) {
            $token = $token->isBlocked()
                ? null
                : $token;
        }

        return $this->render('pages/profile.html.twig', [
            'token' => $token,
            'profile' => $profile,
            'savedNickname' => $clonedProfile->getNickname(),
            'profileDescription' => substr($profileDescription, 0, 200),
            'form' =>  $form->createView(),
            'canEdit' => null !== $user && $profile === $user->getProfile(),
            'editFormShowFirst' => $showEdit,
            'phoneCountryCode' => $profile->getPhoneNumber()
                ? $this->phoneNumberUtil->getRegionCodeForNumber($profile->getPhoneNumber()->getPhoneNumber())
                : null,
        ]);
    }
}
