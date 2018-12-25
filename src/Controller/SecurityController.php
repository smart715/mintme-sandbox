<?php

namespace App\Controller;

use App\Form\CaptchaLoginType;
use FOS\UserBundle\Controller\SecurityController as FOSSecurityController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class SecurityController extends FOSSecurityController
{
    /** @var ContainerInterface $container */
    protected $container;
    
    /** @var FormInterface|null */
    private $form;


    public function __construct(
        ContainerInterface $container,
        ?CsrfTokenManagerInterface $tokenManager = null
    ) {
        $this->container = $container;
        parent::__construct($tokenManager);
    }

    /** @Route("/login", name="login") */
    public function loginAction(Request $request): Response
    {
        $securityContext = $this->container->get('security.authorization_checker');
        if ($securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED') ||
            $securityContext->isGranted('IS_AUTHENTICATED_FULLY')) {
                return $this->redirectToRoute('profile');
        }
        
        $this->form = $this->createForm(CaptchaLoginType::class);
        $this->form->handleRequest($request);
        if ($this->form->isSubmitted() && $this->form->isValid()) {
                return $this->redirectToRoute('fos_user_security_check', [
                    'request' => $request,
                ], 307);
        }
        return parent::loginAction($request);
    }
    
    /**
     * @param array $data
     * @return Response
     */
    protected function renderLogin(array $data): Response
    {
        return $this->render('@FOSUser/Security/login.html.twig', array_merge($data, [
            'form' => $this->form->createView(),
        ]));
    }
}
