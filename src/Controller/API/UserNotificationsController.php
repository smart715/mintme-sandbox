<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Controller\TwoFactorAuthenticatedInterface;
use App\Entity\User;
use App\Manager\UserNotificationManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Rest\Route("/api/notifications")
 */
class UserNotificationsController extends AbstractFOSRestController implements TwoFactorAuthenticatedInterface
{
    private const NOTIFICATION_LIMIT = 90;

    /** @var UserManagerInterface */
    protected $userManager;

    /** @var UserNotificationManagerInterface */
    private $userNotificationManager;

    public function __construct(
        UserManagerInterface $userManager,
        UserNotificationManagerInterface $userNotificationManager
    ) {
        $this->userManager = $userManager;
        $this->userNotificationManager = $userNotificationManager;
    }

    /**
     * @Rest\Get("/user_notifications", name="user_notifications", options={"expose"=true})
     * @Rest\View()
     * @return View
     */
    public function getUserNotifications(): View
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->view([
            $this->userNotificationManager->getNotifications($user, self::NOTIFICATION_LIMIT),
        ], Response::HTTP_ACCEPTED);
    }

    /**
     * @Rest\Get("/update_read_notifications", name="update_read_notifications", options={"expose"=true})
     * @Rest\View()
     * @return Response
     */
    public function updateUserNotification(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $this->userNotificationManager->updateNotifications($user);

        return new Response(Response::HTTP_ACCEPTED);
    }
}
