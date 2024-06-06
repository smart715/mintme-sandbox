<?php declare(strict_types = 1);

namespace App\EventSubscriber;

use FOS\UserBundle\EventListener\AuthenticationListener as BaseAuthenticationListener;
use FOS\UserBundle\FOSUserEvents;

/**
 * @codeCoverageIgnore
 * @phpstan-ignore-next-line final class
 */
class AuthenticationListener extends BaseAuthenticationListener
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FOSUserEvents::REGISTRATION_COMPLETED => 'authenticate',
            FOSUserEvents::REGISTRATION_CONFIRMED => 'authenticate',
        ];
    }
}
