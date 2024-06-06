<?php declare(strict_types = 1);

namespace App\Utils;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Lock\LockFactory as BaseLockFactory;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Lock\Store\FlockStore;
use Symfony\Component\Lock\Store\PdoStore;

class LockFactory
{
    public const LOCK_BALANCE = 'balance-';
    public const LOCK_WITHDRAW_INIT = 'withdraw-init-';
    public const LOCK_WITHDRAW_COMMIT = 'withdraw-commit-';
    public const LOCK_WITHDRAW_AFTER_LOGIN = 'withdraw-login-';
    public const LOCK_WITHDRAW_AFTER_REGISTER = 'withdraw-register-';
    public const LOCK_WITHDRAW_AFTER_PASSWORD_CHANGE = 'withdraw-password-change-';
    public const LOCK_WITHDRAW_AFTER_EMAIL_CHANGE = 'withdraw-email-change-';
    public const LOCK_WITHDRAW_AFTER_PHONE_CHANGE = 'withdraw-phone-change-';
    public const LOCK_WITHDRAW_AFTER_2FA_DISABLE = 'withdraw-2fa-disable-';
    public const LOCK_ORDER = 'order-';

    /** @var EntityManagerInterface */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function createLock(string $resource, ?float $ttl = 300.0, bool $autoRelease = true): LockInterface
    {
        $store = new PdoStore($this->em->getConnection());
        $lockFactory = new BaseLockFactory($store);

        return $lockFactory->createLock($resource, $ttl, $autoRelease);
    }

    public function createFileBasedLock(string $resource): LockInterface
    {
        $store = new FlockStore();
        $lockFactory = new BaseLockFactory($store);

        return $lockFactory->createLock($resource);
    }
}
