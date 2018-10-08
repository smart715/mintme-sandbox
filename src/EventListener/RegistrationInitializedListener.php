<?php

namespace App\EventListener;

use FOS\UserBundle\Event\GetResponseUserEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Listener of the event "visitor is about to register" (he navigates register page).
 * The purpose is to prevent already authenticated users from seeing registration form,
 * and redirect them to profile instead.
 */
class RegistrationInitializedListener
{
    /** @var UrlGeneratorInterface */
    private $router;
    
    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    public function __construct(
        UrlGeneratorInterface $router,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->router = $router;
        $this->authorizationChecker = $authorizationChecker;
    }

    public function onFosuserRegistrationInitialize(GetResponseUserEvent $event): void
    {
        $this->redirectToProfileIfAuthenticated($event);
    }
    
    private function redirectToProfileIfAuthenticated(GetResponseUserEvent $event): void
    {
        if (!$this->authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED'))
            return;

        $event->setResponse(new RedirectResponse(
            $this->router->generate('profile')
        ));
    }
}
