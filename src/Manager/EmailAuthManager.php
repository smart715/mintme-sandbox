<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\User;
use App\Manager\Model\EmailAuthResultModel;
use App\Utils\DateTime;
use DateInterval;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;

class EmailAuthManager implements EmailAuthManagerInterface
{
    public const INVALID_CODE = 'Invalid email verification code';
    public const EXPIRED_CODE = 'Email verification code is expired';
    public const HASH_ALGORITHM = 'sha256';

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var DateTime */
    private $time;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->time = new DateTime();
        $this->entityManager = $entityManager;
    }

    public function checkCode(User $user, string $code): EmailAuthResultModel
    {
        $message = null;

        if ($code !== $user->getEmailAuthCode()) {
            $message = self::INVALID_CODE;
        } elseif ($user->getEmailAuthCodeExpirationTime() < $this->time->now()) {
            $message = self::EXPIRED_CODE;
        }

        return new EmailAuthResultModel($message);
    }

    public function generateCode(User $user, int $expirationTime): string
    {
        $confirmCode = hash(self::HASH_ALGORITHM, Uuid::uuid4()->toString());
        $user->setEmailAuthCode($confirmCode);
        
        $codeExpirationTime = $this->time->now()->add(new DateInterval("PT{$expirationTime}M"));
        $user->setEmailAuthCodeExpirationTime($codeExpirationTime);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        return $confirmCode;
    }
}
