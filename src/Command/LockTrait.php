<?php declare(strict_types = 1);

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\PdoStore;

trait LockTrait
{
    function createLock(Connection $connection, string $resource, $ttl = 300.0, $autoRelease = true)
    {
        $store = new PdoStore($connection);
        $lockFactory = new LockFactory($store);

        return $lockFactory->createLock($resource, $ttl, $autoRelease);
    }
}
