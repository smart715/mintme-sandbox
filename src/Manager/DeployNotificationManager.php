<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\DeployNotification;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Mailer\MailerInterface;
use App\Repository\DeployNotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

class DeployNotificationManager implements DeployNotificationManagerInterface
{
    private EntityManagerInterface $entityManager;
    private DeployNotificationRepository $repository;
    private MailerInterface $mailer;
    private UserTokenFollowManagerInterface $userTokenFollowManager;

    public function __construct(
        EntityManagerInterface $entityManager,
        DeployNotificationRepository $repository,
        MailerInterface $mailer,
        UserTokenFollowManagerInterface $userTokenFollowManager
    ) {
        $this->entityManager = $entityManager;
        $this->repository = $repository;
        $this->mailer = $mailer;
        $this->userTokenFollowManager = $userTokenFollowManager;
    }

    public function createAndNotify(User $notifier, Token $token): void
    {
        $this->entityManager->beginTransaction();

        try {
            $deployNotification = $this->create($notifier, $token);

            $this->mailer->sendDeployNotificationMail($deployNotification);
            $this->userTokenFollowManager->manualFollow($token, $notifier);

            $this->entityManager->commit();
        } catch (\Throwable $exception) {
            $this->entityManager->rollback();

            throw $exception;
        }
    }

    public function alreadyNotified(User $user, Token $token): bool
    {
        return (bool)$this->findByUserAndToken($user, $token);
    }

    public function findByUserAndToken(User $user, Token $token): ?DeployNotification
    {
        return $this->repository->findOneBy(
            [
                'notifier' => $user,
                'token' => $token,
            ]
        );
    }

    private function create(User $notifier, Token $token): DeployNotification
    {
        $deployNotification = new DeployNotification($notifier, $token);

        $this->entityManager->persist($deployNotification);
        $this->entityManager->flush();

        return $deployNotification;
    }
}
