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
use App\Utils\Converter\String\BbcodeMetaTagsStringStrategy;
use App\Utils\Converter\String\StringConverter;
use DateTimeImmutable;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @Route("/profile")
 */
class ProfileController extends Controller
{
    private UserActionLogger $userActionLogger;
    private PhoneNumberUtil $phoneNumberUtil;

    public function __construct(
        NormalizerInterface $normalizer,
        UserActionLogger $userActionLogger,
        PhoneNumberUtil $phoneNumberUtil
    ) {
        parent::__construct($normalizer);
        $this->userActionLogger = $userActionLogger;
        $this->phoneNumberUtil = $phoneNumberUtil;
    }

    /** @Route("/phone/verify", name="phone_verification") */
    public function phoneConfirmation(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $profile = $user->getProfile();

        $form = $this->createForm(PhoneVerificationType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() &&
            $form->isValid() &&
            $form->get('verificationCode')->getData() === $profile->getPhoneNumber()->getVerificationCode()
        ) {
            $profile->getPhoneNumber()->setVerified(true);
            $profile->getPhoneNumber()->setVerificationCode(null);
            $profile->getPhoneNumber()->setEditDate(new DateTimeImmutable());
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($profile);
            $entityManager->flush();

            return $this->redirectToRoute('profile');
        }

        return $this->render('pages/phone_verification.html.twig', [
            'form' => $form->createView(),
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
        $user = $this->getUser();

        if ($user && $profile->getUser() === $user) {
            $profile->setDisabledAnonymous(true);
        }

        if (null === $profile) {
            throw new NotFoundProfileException();
        }

        $e164phoneNumber = $profile->getPhoneNumber() ?
            $this->phoneNumberUtil->format($profile->getPhoneNumber()->getPhoneNumber(), PhoneNumberFormat::E164) :
            null;
        $phoneIsVerified = $profile->getPhoneNumber()
            ? $profile->getPhoneNumber()->isVerified()
            : null;

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

        if ($profile->getPhoneNumber()) {
            $profile->getPhoneNumber()->setProfile($profile);
        }

        $newPhoneNumber = $this->phoneNumberUtil->format(
            $profile->getPhoneNumber()->getPhoneNumber(),
            PhoneNumberFormat::E164
        );
        $verifyPhone = !$e164phoneNumber || $newPhoneNumber !== $e164phoneNumber || !$phoneIsVerified;

        if ($verifyPhone) {
            $profile->getPhoneNumber()->setVerified(false);
            $profile->getPhoneNumber()->setVerificationCode(null);
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

        if ($verifyPhone) {
            return $this->redirectToRoute('phone_verification');
        }

        return $this->redirectToRoute('profile-view', [ 'nickname' => $profile->getNickname() ]);
    }

    /** @Route(name="profile") */
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

        if ($profile->getUser() === $user) {
            $profile->setDisabledAnonymous(true);
        }

        $profileDescription = $profile->getDescription() ?? '';
        $profileDescription = (new StringConverter(new BbcodeMetaTagsStringStrategy()))->convert($profileDescription);
        $profileDescription = preg_replace(
            '/\[\/?(?:b|i|u|s|ul|ol|li|p|s|url|img|h1|h2|h3|h4|h5|h6)*?.*?\]/',
            '\2',
            $profileDescription
        ) ?? '';
        $profileDescription = preg_replace('/[\n\r]+/', ' ', $profileDescription);

        return $this->render('pages/profile.html.twig', [
            'token' => $profile->getMintmeToken(),
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
