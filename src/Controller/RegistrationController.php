<?php

namespace App\Controller;

use FOS\UserBundle\Controller\RegistrationController as FOSRegistrationController;
use FOS\UserBundle\Form\Factory\FormFactory;
use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class RegistrationController extends FOSRegistrationController
{
    /** @var ContainerInterface $container */
    protected $container;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        FormFactory $formFactory,
        UserManagerInterface $userManager,
        TokenStorageInterface $tokenStorage,
        ContainerInterface $container
    ) {
        $this->container = $container;
        parent::__construct($eventDispatcher, $formFactory, $userManager, $tokenStorage);
    }

    public function registerAction(Request $request): Response
    {
        $securityContext = $this->container->get('security.authorization_checker');
        if ($securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED') ||
            $securityContext->isGranted('IS_AUTHENTICATED_FULLY')) {
                return $this->redirectToRoute('profile');
        }

        return parent::registerAction($request);
    }
}
