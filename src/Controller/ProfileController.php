<?php

namespace App\Controller;

use App\Entity\Profile;
use App\Exception\NotFoundProfileException;
use App\Form\AddProfileType;
use App\Form\EditProfileType;
use App\Manager\ProfileManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/profile")
 * @Security(expression="is_granted('prelaunch')")
 */
class ProfileController extends Controller
{
    /** @Route("/{pageUrl}", name="profile-view") */
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

        if (!$profile->isChangesLocked()) {
            $profile->updateNameChangedDate();
        }

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($profile);
        $entityManager->flush();

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

        return $this->redirectToRoute('profile-view', [ 'pageUrl' => $profile->getPageUrl() ]);
    }
}
