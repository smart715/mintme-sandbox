<?php

namespace App\Controller;

use App\Entity\Profile;
use App\Entity\User;
use App\Form\AddProfileType;
use App\Form\EditProfileDescriptionType;
use App\Form\EditProfileType;
use App\Manager\ProfileManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class DefaultController extends Controller
{
     /**
     * @Route("/", name="homepage")
     */
    public function index(): Response
    {
        return $this->render('pages/index.html.twig');
    }

    /**
     * @Route("/trading", name="trading")
     */
    public function trading(): Response
    {
        return $this->render('pages/trading.html.twig');
    }

    /**
     * @Route("/wallet", name="wallet")
     */
    public function wallet(): Response
    {
        return $this->render('pages/wallet.html.twig');
    }

    /**
     * @Route("/profile/{pageUrl}", name="profile")
     */
    public function profile(Request $request, ProfileManagerInterface $profileManagerInterface, ?String $pageUrl = null): Response
    {
        if (!empty($pageUrl)) {
            $profile = $profileManagerInterface->getProfileByPageUrl($pageUrl);
            if (null !== $profile) {
                 return $this->viewProfile($request, $profile, $profileManagerInterface);
            } else {
                return $this->profileNotFoundPage();
            }
        }
        if ($this->getUser()->getProfile())
            return $this->redirect('/profile/'.$this->getUser()->getProfile()->getPageUrl());
        
        return $this->addProfile($request, $profileManagerInterface);
    }
    
    public function viewProfile(Request $request, Profile $profile, ProfileManagerInterface $profileManagerInterface): Response
    {
        $form = $this->createForm(EditProfileType::class, $profile);
        if (!empty($profile->getNameChangedDate()) &&
            $this->getNumberOfDays($profile->getNameChangedDate()) <= 30 ) {
            $form->remove('lastname');
            $form->remove('firstname');
        }
        $form->handleRequest($request);
        if (!$form->isSubmitted() || !$form->isValid())
            return $this->render('pages/profile_view.html.twig', [
                'lastName' => $profile->getLastName(),
                'firstName' => $profile->getFirstName(),
                'city' => $profile->getCity(),
                'country' => $profile->getCountry(),
                'description' => $profile->getDescription(),
                'canedit' => ($profile === $this->getUser()->getProfile()) ? true : false,
                'showeditFormFirst' => (empty($profile->getCity()) && empty($profile->getCountry()) && empty($profile->getDescription())) ? true : false,
                'form' =>  $form->createView(),
            ]);
        
        $profile->setPageUrl($profileManagerInterface->generatePageUrl($profile));
        $today = new \DateTime();
        $today->format('Y-m-d H:i:s');
        $profile->setNameChangedDate($today);
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($profile);
        $entityManager->flush();
        return $this->redirect('/profile/'.$profile->getPageUrl());
    }
    
    public function profileNotFoundPage(): Response
    {
        return $this->render('pages/profile_404.html.twig', []);
    }
    
    public function addProfile(Request $request, ProfileManagerInterface $profileManagerInterface): Response
    {
        $user = $this->getUser();
        $profile  = new Profile($user);
        $form = $this->createForm(AddProfileType::class, $profile);
        $form->handleRequest($request);
        
        if (!$form->isSubmitted() || !$form->isValid())
            return $this->render('pages/profile.html.twig', [
                'form' => $form->createView(),
            ]);
        
        $profile->setPageUrl($profileManagerInterface->generatePageUrl($profile));
        $today = new \DateTime();
        $today->format('Y-m-d H:i:s');
        $profile->setNameChangedDate($today);
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($profile);
        $entityManager->flush();
        return $this->redirect('/profile/'.$profile->getPageUrl());
    }
        
    /**
     * @Route("/token/{name}/{tab}", name="token")
     */
    public function token(?String $name = null, ?String $tab = null): Response
    {
        // FIXME: This data is for view test only.
        $tokenName = $name;
        $action = 'invest';
        $tab = strtolower(strval($tab));
        $name = strtolower(strval($name));
        if (empty($name) && empty($tab)) {
            $action = 'edit';
            if (empty($tokenName))
                $tokenName = 'Dummy Token Name';
        } elseif (!empty($tab)) {
            if ('invest' === $tab || 'intro' === $tab)
                $action = $tab;
        } elseif ('new' === $name) {
            $action = 'new';
            $tokenName = null;
        }

        return $this->render('pages/token.html.twig', [
            'tokenName' => $tokenName,
            'action' => $action,
        ]);
    }
       
    private function getNumberOfDays(\DateTime $from): int
    {
        $today = new \DateTime();
        $today->format('Y-m-d H:i:s');
        $interval = $from->diff($today);
        return intval($interval->format('%R%a'));
    }
}
