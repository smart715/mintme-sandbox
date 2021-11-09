<?php declare(strict_types = 1);

namespace App\Consumers\Helpers;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class DBConnection
{
    public static function reconnectIfDisconnected(EntityManagerInterface &$em): void
    {
        if (false === $em->getConnection()->ping()) {
            $em->getConnection()->close();
            $em->getConnection()->connect();
        }
    }

    public static function initConsumerEm(
        string $consumerName,
        EntityManagerInterface &$em,
        LoggerInterface $logger
    ): bool {
        try {
            self::reconnectIfDisconnected($em);

            return true;
        } catch (\Throwable $exception) {
            sleep(5);
            $logger->error("[{$consumerName}] couldn't connect to DB : {$exception}");

            return false;
        }
    }
}
