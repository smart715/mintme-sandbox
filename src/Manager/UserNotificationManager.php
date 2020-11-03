<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\User;
use App\Entity\UserNotification;
use App\Entity\UserToken;
use App\Exception\ApiBadRequestException;
use App\Mailer\MailerInterface;
use App\Repository\UserNotificationRepository;
use App\Utils\NotificationsType;
use Doctrine\ORM\EntityManagerInterface;

class UserNotificationManager implements UserNotificationManagerInterface
{
    /** @var EntityManagerInterface */
    private $em;

    /** @var UserNotificationRepository */
    private $userNotificationRepository;

    /** @var MailerInterface */
    private MailerInterface $mailer;

    public function __construct(
        EntityManagerInterface $em,
        UserNotificationRepository $userNotificationRepository,
        MailerInterface $mailer
    ) {
        $this->em = $em;
        $this->userNotificationRepository =  $userNotificationRepository;
        $this->mailer = $mailer;
    }

    public function createNotification(
        User $user,
        String $notificationType,
        array $extraData
    ): void {
        if ((NotificationsType::TOKEN_NEW_POST === $notificationType ||
            NotificationsType::TOKEN_DEPLOYED === $notificationType)
        ) {
            foreach ($this->getUsersHaveTokenIds($user) as $userHaveToken) {
                $token = $user->getProfile()->getToken();
                $userWithToken = $this->em->getRepository(User::class)->find($userHaveToken);

                $tokenName = $token->getName();
                $jsonData = (array)json_encode([
                    'tokenName' => $tokenName,
                ], JSON_THROW_ON_ERROR);

                $this->newUserNotification($notificationType, $userWithToken, $jsonData);
                NotificationsType::TOKEN_NEW_POST === $notificationType ?
                    $this->mailer->sendNewPostMail($userWithToken, $tokenName) :
                    $this->mailer->sendTokenDeployedMail($userWithToken, $tokenName);
            }

            $this->em->flush();
        } else {
            $jsonData = null;

            if (NotificationsType::ORDER_CANCELLED === $notificationType ||
                NotificationsType::ORDER_FILLED === $notificationType
            ) {
                $tokenName = $user->getProfile()->getToken()->getName();
                $jsonData = (array)json_encode([
                        'tokenName' => $tokenName,
                    ], JSON_THROW_ON_ERROR);
                $this->mailer->sendNoOrdersMail($user, $tokenName);
            }

            if (NotificationsType::NEW_INVESTOR === $notificationType) {
                $jsonData = (array)json_encode(
                    $extraData,
                    JSON_THROW_ON_ERROR
                );
                $this->mailer->sendNewInvestorMail($user, $extraData['profile']);
            }

            $this->newUserNotification($notificationType, $user, $jsonData);
            $this->em->flush();
        }
    }

    public function getNotifications(User $user, ?int $notificationLimit): ?array
    {
        $notifications = $this->userNotificationRepository->findUserNotifications($user, $notificationLimit);

        return $this->userNotificationsFactory($notifications);
    }

    private function userNotificationsFactory(?array $notifications): array
    {
        if (!$notifications) {
            return [];
        }

        $result =[];

        foreach ($notifications as $key => $notification) {
            $jsonExtraData = $notification->getJsonData();
            $notificationType = $notification->getType();
            $result[$key]['id'] = $notification->getId();
            $result[$key]['type'] = $notificationType;
            $result[$key]['viewed'] = $notification->getViewed();
            $result[$key]['extraData'] = $jsonExtraData ?
                json_decode($jsonExtraData[0], true, 512, JSON_THROW_ON_ERROR) :
                $jsonExtraData;
        }

        return $result;
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

    private function getUsersHaveTokenIds(User $user): array
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
    }

    private function newUserNotification(String $type, User $user, ?array $jsonData): void
    {
        $userNotification = (new UserNotification())
            ->setType($type)
            ->setUser($user);

        if ($jsonData) {
            $userNotification->setJsonData($jsonData);
        }

        $userNotification->setViewed(false);
        $this->em->persist($userNotification);
    }
}
