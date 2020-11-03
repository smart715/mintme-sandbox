<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\ScheduledNotification;
use App\Entity\User;
use App\Entity\UserNotification;
use App\Repository\ScheduledNotificationRepository;
use App\Utils\NotificationType;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

class ScheduledNotificationManager implements ScheduledNotificationManagerInterface
{
    /** @var array  */
    public array $timeIntervals;

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

    public function createScheduledNotification(String $notificationType, User $user): void
    {
        $existScheduleNotification = $this->scheduledNotificationRepository->findByUser($user);

        if ($existScheduleNotification) {
            $this->removeScheduledNotification($existScheduleNotification->getId());
        }

        $scheduledNotification = (new ScheduledNotification())
            ->setType($notificationType)
            ->setUser($user)
            ->setDateToBeSend($this->dateToBeSendFactory($notificationType));

        if (NotificationType::ORDER_CANCELLED === $notificationType) {
            $scheduledNotification->setTimeInterval((string)$this->timeIntervals[1]); // 24 hrs
        } else {
            $scheduledNotification->setTimeInterval((string)$this->timeIntervals[0]); // 10min
        }

        $this->em->persist($scheduledNotification);
        $this->em->flush();
    }

    public function updateScheduledNotification(
        ScheduledNotification $scheduledUserNotification,
        String $newTimeInterval,
        \DateTimeImmutable $newTimeToBeSend
    ): void {
        $scheduledUserNotification->setTimeInterval($newTimeInterval)
            ->setDateToBeSend($newTimeToBeSend);

        $this->em->persist($scheduledUserNotification);
        $this->em->flush();
    }

    public function removeScheduledNotification(int $scheduledNotificationId): int
    {
        return $this->scheduledNotificationRepository->deleteScheduledNotification($scheduledNotificationId);
    }

    private function dateToBeSendFactory(string $orderExecutionType): \DateTimeImmutable
    {
        $actualDate = new DateTimeImmutable();

        return NotificationType::ORDER_CANCELLED === $orderExecutionType ?
             $actualDate->modify('+'.$this->timeIntervals[1].' minutes') :  // one day
             $actualDate->modify('+'.$this->timeIntervals[0].' minutes');  // 10 min
    }
}
