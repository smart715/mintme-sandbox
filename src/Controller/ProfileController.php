<?php declare(strict_types = 1);

namespace App\Controller;

use App\Entity\Profile;
use App\Exception\NotFoundProfileException;
use App\Form\AddProfileType;
use App\Form\EditProfileType;
use App\Logger\UserActionLogger;
use App\Manager\ProfileManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @Route("/profile")
 * @Security(expression="is_granted('prelaunch')")
 */
class ProfileController extends Controller
{
    /** @var UserActionLogger */
    private $userActionLogger;

    public function __construct(NormalizerInterface $normalizer, UserActionLogger $userActionLogger)
    {
        parent::__construct($normalizer);
        $this->userActionLogger = $userActionLogger;
    }

    /** @Route("/{pageUrl}", name="profile-view", options={"expose"=true}) */
    public function profileView(
        Request $request,
        ProfileManagerInterface $profileManager,
        string $pageUrl
    ): Response {
        $profile = $profileManager->getProfileByPageUrl($pageUrl);

        if (null === $profile) {
            throw new NotFoundProfileException();
        }

        $form = $this->createForm(EditProfileType::class, $profile);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render('pages/profile_view.html.twig', [
                'token' => $profile->getToken(),
                'profile' => $profile,
                'form' =>  $form->createView(),
                'canEdit' => null !== $this->getUser() && $profile === $this->getUser()->getProfile(),
                'editFormShowFirst' => !! $form->getErrors(true)->count(),
            ]);
        }

        $profile->setPageUrl($profileManager->generatePageUrl($profile));

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->merge($profile);
        $entityManager->flush();

        $this->userActionLogger->info('Edit profile');

        return $this->redirectToRoute('profile-view', [ 'pageUrl' => $profile->getPageUrl() ]);
    }

    /** @Route(name="profile") */
    public function profile(
        Request $request,
        ProfileManagerInterface $profileManager
    ): Response {
        $profile = $profileManager->getProfile($this->getUser());

        if (null !== $profile) {
            return $this->redirectToRoute('profile-view', [ 'pageUrl' => $profile->getPageUrl() ]);
        }

        $profile  = new Profile($this->getUser());
        $form = $this->createForm(AddProfileType::class, $profile);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render('pages/profile.html.twig', [
                'form' => $form->createView(),
            ]);
        }

        $profile->setPageUrl($profileManager->generatePageUrl($profile));

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($profile);
        $entityManager->flush();

        $this->userActionLogger->info('Create profile');

        return $this->redirectToRoute('profile-view', [ 'pageUrl' => $profile->getPageUrl() ]);
    }
}
