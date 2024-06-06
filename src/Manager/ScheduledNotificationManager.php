<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\ScheduledNotification;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Repository\ScheduledNotificationRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

class ScheduledNotificationManager implements ScheduledNotificationManagerInterface
{
    public array $filled_intervals;
    public array $cancelled_intervals;
    public array $token_marketing_tips_intervals;
    public array $token_promotion_tips_intervals;
    public array $marketing_airdrop_feature;

    private EntityManagerInterface $em;
    private ScheduledNotificationRepository $scheduledNotificationRepository;

    public function __construct(
        EntityManagerInterface $em,
        ScheduledNotificationRepository $scheduledUserNotificationRepository
    ) {
        $this->em = $em;
        $this->scheduledNotificationRepository =  $scheduledUserNotificationRepository;
    }

    public function getScheduledNotifications(): ?array
    {
        return $this->scheduledNotificationRepository->findAll();
    }

    public function createScheduledTokenNotification(
        string $notificationType,
        Token $token,
        bool $flush = true
    ): ScheduledNotification {
        return $this->performCreateScheduledNotification(
            $notificationType,
            $token->getOwner(),
            $token,
            $flush,
        );
    }

    public function createScheduledNotification(
        string $notificationType,
        User $user,
        bool $flush = true
    ): ScheduledNotification {
        return $this->performCreateScheduledNotification(
            $notificationType,
            $user,
            null,
            $flush,
        );
    }

    private function performCreateScheduledNotification(
        string $notificationType,
        User $user,
        ?Token $token = null,
        bool $flush = true
    ): ScheduledNotification {
        $existScheduleNotifications = $this->scheduledNotificationRepository->findBy(['user' => $user->getId()]);

        foreach ($existScheduleNotifications as $schNotification) {
            $type = $schNotification->getType();

            if ($type === $notificationType) {
                $this->removeScheduledNotification($schNotification->getId());
            }
        }

        $scheduledNotification = (new ScheduledNotification())
            ->setType($notificationType)
            ->setUser($user)
            ->setDateToBeSend($this->setDate($notificationType))
            ->setTimeInterval((string)$this->{strtolower($notificationType) . '_intervals'}[0]);

        if ($token) {
            $scheduledNotification->setToken($token);
        }

        if ($flush) {
            $this->em->persist($scheduledNotification);
            $this->em->flush();
        }

        return $scheduledNotification;
    }

    public function updateScheduledNotification(
        ScheduledNotification $scheduledNotification,
        string $newTimeInterval,
        \DateTimeImmutable $newTimeToBeSend
    ): void {
        $scheduledNotification->setTimeInterval($newTimeInterval)
            ->setDateToBeSend($newTimeToBeSend);

        $this->em->persist($scheduledNotification);
        $this->em->flush();
    }

    public function removeScheduledNotification(int $scheduledNotificationId): int
    {
        return $this->scheduledNotificationRepository->deleteScheduledNotification($scheduledNotificationId);
    }

    public function removeByTypeForUser(string $type, User $user): int
    {
        return $this->scheduledNotificationRepository->removeByTypeForUser($type, $user);
    }

    private function setDate(string $notificationType): \DateTimeImmutable
    {
        $actualDate = new DateTimeImmutable();

        return $actualDate->modify('+' . $this->{strtolower($notificationType) . '_intervals'}[0]);
    }
}
