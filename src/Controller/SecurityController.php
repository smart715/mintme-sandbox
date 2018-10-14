<?php

namespace App\Controller;

use FOS\UserBundle\Controller\SecurityController as FOSSecurityController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class SecurityController extends FOSSecurityController
{
    /** @var ContainerInterface $container */
    protected $container;

    public function __construct(
        ContainerInterface $container,
        ?CsrfTokenManagerInterface $tokenManager = null
    ) {
        $this->container = $container;
        parent::__construct($tokenManager);
    }

    public function loginAction(Request $request): Response
    {
        $securityContext = $this->container->get('security.authorization_checker');
        if ($securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED') ||
            $securityContext->isGranted('IS_AUTHENTICATED_FULLY')) {
                return $this->redirectToRoute('referral');
        }

        return parent::loginAction($request);
    }
}
