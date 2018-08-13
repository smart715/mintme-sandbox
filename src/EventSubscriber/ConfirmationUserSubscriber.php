<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use FOS\UserBundle\FOSUserEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConfirmationUserSubscriber implements EventSubscriberInterface
{
    /** @var EntityManagerInterface */
    private $orm;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->orm = $entityManager;
    }

    /** {@inheritdoc} */
    public static function getSubscribedEvents(): array
    {
        return [
            FOSUserEvents::REGISTRATION_CONFIRMED => 'onConfirmed',
        ];
    }

    public function onConfirmed(FilterUserResponseEvent $event): void
    {
        /** @var User $user */
        $user = $event->getUser();

        if (empty($user->getTempEmail()))
            return;

        $user->setEmail($user->getTempEmail());
        $user->setTempEmail(null);
        $this->orm->persist($user);
        $this->orm->flush();
    }
}
