<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Controller\TwoFactorAuthenticatedInterface;
use App\Entity\User;
use App\Manager\UserNotificationConfigManagerInterface;
use App\Manager\UserNotificationManagerInterface;
use App\Utils\NotificationChannels;
use App\Utils\NotificationTypes;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @Rest\Route("/api/userNotifications")
 */
class UserNotificationsController extends AbstractFOSRestController implements TwoFactorAuthenticatedInterface
{
    private const NOTIFICATION_LIMIT = 90;

    /** @var UserManagerInterface */
    protected $userManager;

    /** @var UserNotificationManagerInterface */
    private $userNotificationManager;

    /** @var UserNotificationConfigManagerInterface */
    private $userNotificationsConfig;

    public function __construct(
        UserManagerInterface $userManager,
        UserNotificationManagerInterface $userNotificationManager,
        UserNotificationConfigManagerInterface $userNotificationsConfig
    ) {
        $this->userManager = $userManager;
        $this->userNotificationManager = $userNotificationManager;
        $this->userNotificationsConfig = $userNotificationsConfig;
    }

    /**
     * @Rest\Get("/user-notifications", name="user-notifications", options={"expose"=true})
     * @Rest\View()
     * @return View
     */
    public function getUserNotifications(): View
    {
        /** @var User|null $user */
        $user = $this->getUser();

        return $this->view([
            'data' => $this->userNotificationManager->getNotifications($user, self::NOTIFICATION_LIMIT),
        ], Response::HTTP_ACCEPTED);
    }

    /**
     * @Rest\Get("/update-read-notifications", name="update-read-notifications", options={"expose"=true})
     * @Rest\View()
     * @return Response
     */
    public function updateUserNotification(): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();

        $this->userNotificationManager->updateNotifications($user);

        return new Response(Response::HTTP_ACCEPTED);
    }

    /**
     * @Rest\Get("/user-notifications-config", name="user_notifications_config", options={"expose"=true})
     * @Rest\View()
     * @return View
     */
    public function getUserNotificationsConfig(): View
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->view(
            $this->userNotificationsConfig->getUserNotificationsConfig($user),
            Response::HTTP_OK
        );
    }

    /**
     * @Rest\Get("/notification-types", name="notification_types", options={"expose"=true})
     * @Rest\View()
     * @return View
     */
    public function getNotificationsType(): View
    {
        return $this->view(
            NotificationTypes::getAll(),
            Response::HTTP_OK
        );
    }

    /**
     * @Rest\Get("/notification-channels", name="notification_channels", options={"expose"=true})
     * @Rest\View()
     * @return View
     */
    public function getNotificationsChannel(): View
    {
        return $this->view(
            NotificationChannels::getAll(),
            Response::HTTP_OK
        );
    }




    /**
     * @return UserInterface|object|null
     */
    protected function getUser()
    {
        return parent::getUser();
    }
}
