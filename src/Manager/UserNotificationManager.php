<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\User;
use App\Entity\UserNotification;
use App\Exception\ApiBadRequestException;
use App\Repository\UserNotificationRepository;
use App\Utils\NotificationTypes;
use Doctrine\ORM\EntityManagerInterface;

class UserNotificationManager implements UserNotificationManagerInterface
{
    /** @var EntityManagerInterface */
    private EntityManagerInterface $em;

    /** @var UserNotificationRepository */
    private UserNotificationRepository $userNotificationRepository;

    /** @var UserNotificationConfigManagerInterface */
    private UserNotificationConfigManagerInterface $notificationConfigManager;

    public function __construct(
        EntityManagerInterface $em,
        UserNotificationRepository $userNotificationRepository,
        UserNotificationConfigManagerInterface $notificationConfigManager
    ) {
        $this->em = $em;
        $this->userNotificationRepository =  $userNotificationRepository;
        $this->notificationConfigManager = $notificationConfigManager;
    }

    public function createNotification(
        User $user,
        String $notificationType,
        ?array $extraData
    ): void {
        $userNotification = (new UserNotification())
            ->setType($notificationType)
            ->setUser($user);

        if ($extraData) {
            $userNotification->setJsonData($extraData);
        }

        $userNotification->setViewed(false);
        $this->em->persist($userNotification);
        $this->em->flush();

        /*if ((NotificationTypes::TOKEN_NEW_POST === $notificationType ||
            NotificationTypes::TOKEN_DEPLOYED === $notificationType)
        ) {
            foreach ($this->getUsersHaveTokenIds($user) as $userHaveToken) {
                $token = $user->getProfile()->getToken();
                $userWithToken = $this->em->getRepository(User::class)->find($userHaveToken);

                $tokenName = $token->getName();
                $jsonData = (array)json_encode([
                    'tokenName' => $tokenName,
                ], JSON_THROW_ON_ERROR);

                if ($this->isNotificationAvailable($user, $notificationType, NotificationChannels::EMAIL)) {
                    $this->newUserNotification($notificationType, $userWithToken, $jsonData);
                }

                if ($this->isNotificationAvailable($user, $notificationType, NotificationChannels::WEBSITE)) {
                    NotificationTypes::TOKEN_NEW_POST === $notificationType ?
                        $this->mailer->sendNewPostMail($userWithToken, $tokenName) :
                        $this->mailer->sendTokenDeployedMail($userWithToken, $tokenName);
                }
            }

            $this->em->flush();
        } else {
            $jsonData = null;

            if (NotificationTypes::ORDER_CANCELLED === $notificationType ||
                NotificationTypes::ORDER_FILLED === $notificationType
            ) {
                $tokenName = $user->getProfile()->getToken()->getName();
                $jsonData = (array)json_encode([
                        'tokenName' => $tokenName,
                    ], JSON_THROW_ON_ERROR);
                $this->mailer->sendNoOrdersMail($user, $tokenName);
            }

            if (NotificationTypes::NEW_INVESTOR === $notificationType) {
                $jsonData = (array)json_encode(
                    $extraData,
                    JSON_THROW_ON_ERROR
                );

                if ($this->isNotificationAvailable($user, $notificationType, NotificationChannels::EMAIL)) {
                    $this->mailer->sendNewInvestorMail($user, $extraData['profile']);
                }
            }

            if ($this->isNotificationAvailable($user, $notificationType, NotificationChannels::WEBSITE)) {
                $this->newUserNotification($notificationType, $user, $jsonData);
            }

            $this->em->flush();
        }*/
    }

    public function getNotifications(User $user, ?int $notificationLimit): ?array
    {
        return $this->userNotificationRepository->findUserNotifications($user, $notificationLimit);
    }

    public function updateNotifications(User $user): void
    {
        $notifications =  $this->userNotificationRepository->findUserNotifications($user, null);

        if (!$notifications) {
            throw new ApiBadRequestException('Internal error, Please try again later');
        }

        foreach ($notifications as $notification) {
            $notification->setViewed(true);
            $this->em->persist($notification);
        }

        $this->em->flush();
    }

    /*private function getUsersHaveTokenIds(User $user): array
    {
        $result = [];
        $usersTokens = $this->em->getRepository(UserToken::class)->findAll();

        if (!$usersTokens) {
            return $result;
        }

        foreach ($usersTokens as $userToken) {
            $tokenId = $user->getProfile()->getToken()->getId();
            $userId = $user->getId();
            $userWithToken = $userToken->getuser()->getId();
            $userTokenId = $userToken->getToken()->getId();

            if ($userTokenId === $tokenId && $userWithToken !== $userId) {
                $result[] = $userWithToken;
            }
        }

        return $result;
    }*/

    /*private function newUserNotification(String $type, User $user, ?array $jsonData): void
    {
        $userNotification = (new UserNotification())
            ->setType($type)
            ->setUser($user);

        if ($jsonData) {
            $userNotification->setJsonData($jsonData);
        }

        $userNotification->setViewed(false);
        $this->em->persist($userNotification);
    }*/

    public function isNotificationAvailable(User $user, String $type, String $channel): Bool
    {
        if (NotificationTypes::ORDER_FILLED === $type || NotificationTypes::ORDER_CANCELLED === $type) {
            return true;
        }

        $userConfig = $this->notificationConfigManager->getOneUserNotificationConfig($user, $type, $channel);

        return null !== $userConfig;
    }
}
