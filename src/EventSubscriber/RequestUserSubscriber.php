<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Manager\ProfileManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class RequestUserSubscriber implements EventSubscriberInterface
{
    /** @var ProfileManagerInterface  */
    private $profileManager;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    public function __construct(ProfileManagerInterface $profileManager, TokenStorageInterface $tokenStorage)
    {
        $this->profileManager = $profileManager;
        $this->tokenStorage = $tokenStorage;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST=> 'onRequest',
        ];
    }

    public function onRequest(GetResponseEvent $request): void
    {
        if (is_object($this->tokenStorage->getToken()) &&
            is_object($this->tokenStorage->getToken()->getUser()) &&
            !$request->getRequest()->isXmlHttpRequest()
        ) {
            /** @var User $user */
            $user = $this->tokenStorage->getToken()->getUser();
            $this->profileManager->createHash($user);
        }
    }
}
