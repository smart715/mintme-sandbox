<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Controller\Traits\ViewOnlyTrait;
use App\Controller\TwoFactorAuthenticatedInterface;
use App\Entity\User;
use App\Exception\ApiForbiddenException;
use App\Manager\UserNotificationConfigManagerInterface;
use App\Manager\UserNotificationManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @Rest\Route("/api/notifications")
 */
class UserNotificationsController extends AbstractFOSRestController implements TwoFactorAuthenticatedInterface
{
    private const NOTIFICATION_LIMIT = 90;

    protected UserManagerInterface $userManager;
    private UserNotificationManagerInterface $userNotificationManager;
    private UserNotificationConfigManagerInterface $userNotificationsConfigManager;
    protected SessionInterface $session;

    use ViewOnlyTrait;

    public function __construct(
        UserManagerInterface $userManager,
        UserNotificationManagerInterface $userNotificationManager,
        UserNotificationConfigManagerInterface $userNotificationsConfigManager,
        SessionInterface $session
    ) {
        $this->userManager = $userManager;
        $this->userNotificationManager = $userNotificationManager;
        $this->userNotificationsConfigManager = $userNotificationsConfigManager;
        $this->session = $session;
    }

    /**
     * @Rest\Get("/user-notifications", name="user_notifications", options={"expose"=true})
     * @Rest\View()
     * @return View
     */
    public function getUserNotifications(): View
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->view(
            $this->userNotificationManager->getNotifications($user, self::NOTIFICATION_LIMIT),
            Response::HTTP_OK
        );
    }

    /**
     * @Rest\Get("/update-read-notifications", name="update_read_notifications", options={"expose"=true})
     * @Rest\View()
     * @return Response
     */
    public function updateUserNotification(): Response
    {
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

        /** @var User $user */
        $user = $this->getUser();

        $this->userNotificationManager->updateNotifications($user);

        return new Response(Response::HTTP_OK);
    }

    /**
     * @Rest\Get("/config", name="user_notifications_config", options={"expose"=true})
     * @Rest\View()
     * @return View
     */
    public function getUserNotificationsConfig(): View
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->view(
            $this->userNotificationsConfigManager->getUserNotificationsConfig($user),
            Response::HTTP_OK
        );
    }

    /**
     * @Rest\Post("/config/update", name="update_notifications_config", options={"expose"=true})
     * @Rest\View()
     * @param Request $request
     * @return Response
     */
    public function updateUserNotificationsConfig(Request $request): Response
    {
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

        /** @var User $user */
        $user = $this->getUser();

        $configToStore = $request->request->all();

        $this->userNotificationsConfigManager->updateUserNotificationsConfig($user, $configToStore);

        return new Response(Response::HTTP_OK);
    }
}
