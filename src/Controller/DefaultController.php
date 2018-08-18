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
        return $this->render('default/index.html.twig');
    }

    /**
     * @Route("/trading", name="trading")
     */
    public function trading(): Response
    {
        return $this->render('default/trading.html.twig');
    }

    /**
     * @Route("/wallet", name="wallet")
     */
    public function wallet(): Response
    {
        return $this->render('default/wallet.html.twig');
    }

    /**
     * @Route("/profile/{pageUrl}", name="profile")
     * @var string|null
     */
    public function profile(
        Request $request,
        ProfileManagerInterface $profileManagerInterface,
        SerializerInterface $serializer,
        ?String $pageUrl = null
    ): Response {
        if (!empty($pageUrl)) {
            $profile = $profileManagerInterface->getProfileByPageUrl($pageUrl);
            if (null !== $profile) {
                 return $this->viewProfile($profile, $serializer);
            } else {
                return $this->profileNotFoundPage();
            }
        }
        $profile = $profileManagerInterface->getProfile($this->getUser());
        if (null === $profile)
            return $this->addProfile($request, $profileManagerInterface);
        
        return $this->viewProfile($profile, $serializer);
    }
    
    public function viewProfile(
        Profile $profile,
        SerializerInterface $serializer
    ): Response {
        return $this->render('default/profile_view.html.twig', [
            'profile' => $serializer->serialize($profile, 'json'),
            'canedit' => ($profile->getUser() === $this->getUser())? true : false,
        ]);
    }
    
    public function profileNotFoundPage(): Response
    {
        return $this->render('pages/profile_error.html.twig', []);
    }
    
    public function addProfile(
        Request $request,
        ProfileManagerInterface $profileManagerInterface
    ): Response {
        $user = $this->getUser();
        $profile  = new Profile($user);
        $form = $this->createForm(AddProfileType::class, $profile);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $profile = $form->getData();
            $profile->setPageUrl($profileManagerInterface->generatePageUrl($profile));
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($profile);
            $entityManager->flush();
            return $this->viewProfile($profile);
        }
        return $this->render('default/profile_form.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    
    /**
     * @Route("/profile-edit", name="profile_edit")
     */
    public function editProfile(
        Request $request,
        ProfileManagerInterface $profileManagerInterface,
        SerializerInterface $serializer
    ): Response {
        $profile = $profileManagerInterface->getProfile($this->getUser());
        $form = $this->createForm(EditProfileType::class, $profile, [
            'action' => $this->generateUrl('profile_edit'),
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $profile = $form->getData();
            $profile->setPageUrl($profileManagerInterface->generatePageUrl($profile));
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($profile);
            $entityManager->flush();
            return new JsonResponse([
                'action' => 'edit-profile',
                'profile' => json_decode($serializer->serialize($profile, 'json')),
                'message' => 'Profile  was saved successfully.',
            ]);
        }
        return $this->renderAjaxForm($form);
    }
    
    /**
     * @Route("/profile-description-edit", name="profile_description_edit")
     */
    public function editProfileDescription(
        Request $request,
        ProfileManagerInterface $profileManagerInterface,
        SerializerInterface $serializer
    ): Response {
        $profile = $profileManagerInterface->getProfile($this->getUser());
        $form = $this->createForm(EditProfileDescriptionType::class, $profile, [
            'action' => $this->generateUrl('profile_description_edit'),
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $profile = $form->getData();
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($profile);
            $entityManager->flush();
            return new JsonResponse([
                'action' => 'edit-profile-description',
                'profile' => json_decode($serializer->serialize($profile, 'json')),
                'message' => 'Profile description was saved successfully.',
            ]);
        }
        return $this->renderAjaxForm($form);
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

        return $this->render('default/token.html.twig', [
            'tokenName' => $tokenName,
            'action' => $action,
        ]);
    }
    
    
    private function renderAjaxForm(FormInterface $form, string $header = ''): Response
    {
        $template = $this->renderView('default/ajax_form.html.twig', [
            'form' => $form->createView(),
        ]);
        return new JsonResponse([
            'header' => $header,
            'body' => $template,
        ]);
    }
}
