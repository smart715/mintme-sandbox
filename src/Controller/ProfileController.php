<?php

namespace App\Controller;

use App\Entity\Profile;
use App\Entity\User;
use App\Form\AddProfileType;
use App\Form\EditProfileDescriptionType;
use App\Form\EditProfileType;
use App\Manager\ProfileManagerInterface;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class ProfileController extends Controller
{
    /**
     * @Route("/profile/{pageUrl}", name="profile")
     */
    public function profile(
        SessionInterface $session,
        Request $request,
        ProfileManagerInterface $profileManager,
        ?string $pageUrl = null
    ): Response {
        if (!empty($pageUrl)) {
            $profile = $profileManager->getProfileByPageUrl($pageUrl);
            if (null === $profile) {
                throw new NotFoundHttpException();
            }
            return $this->viewProfile($session, $request, $profile, $profileManager);
        }
        
        $profile = $profileManager->getProfile($this->getUser());
        if (null !== $profile) {
            return $this->redirectToRoute('profile', array('pageUrl' => $profile->getPageUrl()));
        }
        
        return $this->addProfile($session, $request, $profileManager);
    }
    
    private function viewProfile(
        SessionInterface $session,
        Request $request,
        Profile $profile,
        ProfileManagerInterface $profileManager
    ): Response {
        $form = $this->createForm(EditProfileType::class, $profile);
        $form->handleRequest($request);
        
        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render('pages/profile_view.html.twig', [
                'lastName' => $profile->getLastName(),
                'firstName' => $profile->getFirstName(),
                'city' => $profile->getCity(),
                'country' => $profile->getCountry(),
                'description' => $profile->getDescription(),
                'canEdit' => ($profile === $this->getUser()->getProfile()) ? true : false,
                'editFormShowFirst' => $session->get('editFormShowFirst'),
                'form' =>  $form->createView(),
            ]);
        }
        
        $profile->setPageUrl($profileManager->generatePageUrl($profile));
        $profile->setNameChangedDate(new DateTime());
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($profile);
        $entityManager->flush();
        $session->set('editFormShowFirst', false);
        return  $this->redirectToRoute('profile', array('pageUrl' => $profile->getPageUrl()));
    }
    
    private function addProfile(
        SessionInterface $session,
        Request $request,
        ProfileManagerInterface $profileManager
    ): Response {
        $user = $this->getUser();
        $profile  = new Profile($user);
        $form = $this->createForm(AddProfileType::class, $profile);
        $form->handleRequest($request);
        
        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render('pages/profile.html.twig', [
                'form' => $form->createView(),
            ]);
        }
        
        $profile->setPageUrl($profileManager->generatePageUrl($profile));
        $profile->setNameChangedDate(new DateTime());
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($profile);
        $entityManager->flush();
        $session->set('editFormShowFirst', true);
        return  $this->redirectToRoute('profile', array('pageUrl' => $profile->getPageUrl()));
    }
}
