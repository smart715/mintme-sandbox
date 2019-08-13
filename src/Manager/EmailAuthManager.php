<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class EmailAuthManager implements EmailAuthManagerInterface
{
    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function checkCode(User $user, string $code): array
    {
        $message = null;

        if ($code !== $user->getEmailAuthCode()) {
            $message = 'Invalid 2fa code';
        } elseif (date_timestamp_get($user->getEmailAuthCodeExpirationTime()) - time() < 0) {
            $message = '2fa code is expired';
        }

        return [
            'result' => null === $message,
            'message' => $message,
        ];
    }

    public function generateCode(User $user, int $expirationTime): string
    {
        $confirmCode = (string) rand(1000000, 9999999);
        $user->setEmailAuthCode($confirmCode);
        
        $codeExpirationTime = (new \DateTimeImmutable())->setTimestamp(time() + $expirationTime*60);
        $user->setEmailAuthCodeExpirationTime($codeExpirationTime);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        return $confirmCode;
    }
}
