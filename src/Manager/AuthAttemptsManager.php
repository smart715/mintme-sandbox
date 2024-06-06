<?php declare(strict_types = 1);

namespace App\Manager;

use App\Config\FailedLoginConfig;
use App\Entity\AuthAttempts;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class AuthAttemptsManager implements AuthAttemptsManagerInterface
{
    private EntityManagerInterface $entityManager;
    private FailedLoginConfig $failedLoginConfig;

    public function __construct(EntityManagerInterface $entityManager, FailedLoginConfig $failedLoginConfig)
    {
        $this->entityManager = $entityManager;
        $this->failedLoginConfig = $failedLoginConfig;
    }

    public function decrementChances(User $user): int
    {
        $authAttempts = $user->getAuthAttempts();
        $authAttempts = $this->preDecrementChances($user, $authAttempts);
        $this->entityManager->persist($authAttempts);
        $this->entityManager->flush();

        return $authAttempts->getChances();
    }

    public function initChances(User $user): void
    {
        $user->getAuthAttempts()->setChances($this->failedLoginConfig->getMaxChances());
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public function canDecrementChances(User $user): bool
    {
        $authAttempts = $user->getAuthAttempts();

        return null === $authAttempts ||
             0 !== $authAttempts->getChances() ||
            $this->getWaitedHours($user) >= $this->failedLoginConfig->getMaxHours();
    }

    public function getWaitedHours(User $user): int
    {
        $now = (new \DateTimeImmutable())->getTimestamp();
        $waitedTime =  $now - $user->getAuthAttempts()->getUpdatedAt()->getTimestamp();

        return (int) ($waitedTime / 3600);
    }

    public function getMustWaitHours(User $user): int
    {
        $mustWait = (int) ($this->failedLoginConfig->getMaxHours() - $this->getWaitedHours($user));

        return 0 < $mustWait
            ? $mustWait
            : 1;
    }

    private function preDecrementChances(User $user, ?AuthAttempts $authAttempts): AuthAttempts
    {
        if (!$authAttempts) {
            $authAttempts = new AuthAttempts();
            $authAttempts->setChances($this->failedLoginConfig->getMaxChances() - 1);

            return $authAttempts->setUser($user);
        }

        if (0 === $authAttempts->getChances()) {
            return $authAttempts->setChances($this->failedLoginConfig->getMaxChances() - 1);
        }

        return $authAttempts->setChances($authAttempts->getChances() - 1);
    }
}
