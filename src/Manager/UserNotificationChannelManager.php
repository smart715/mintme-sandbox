<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\User;
use App\Repository\UserNotificationChannelRepository;
use Doctrine\ORM\EntityManagerInterface;

class UserNotificationChannelManager implements UserNotificationChannelManagerInterface
{
    /** @var EntityManagerInterface */
    private EntityManagerInterface $em;

    /** @var UserNotificationChannelRepository */
    private UserNotificationChannelRepository $userNotificationChannelRepository;


    public function __construct(
        EntityManagerInterface $em,
        UserNotificationChannelRepository $userNotificationChannelRepository
    ) {
        $this->em = $em;
        $this->userNotificationChannelRepository = $userNotificationChannelRepository;
    }

    public function getUserNotificationsChannel(User $user): ?array
    {
        // TODO: Implement getUserNotificationsChannel() method.
    }
}
