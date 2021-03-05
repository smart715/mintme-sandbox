<?php declare(strict_types = 1);

namespace App\Utils;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Lock\LockFactory as BaseLockFactory;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Lock\Store\PdoStore;

class LockFactory
{
    public const LOCK_BALANCE = 'balance-';

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
}
