<?php declare(strict_types = 1);

namespace App\Consumers\Helpers;

use Doctrine\ORM\EntityManagerInterface;

class DBConnection
{
    public static function reconnectIfDisconnected(EntityManagerInterface $em): void
    {
        try {
            $em->getConnection()->executeQuery('SELECT 1')->closeCursor();
        } catch (\Throwable $e) {
            $em->getConnection()->close();
            $em->getConnection()->connect();
        }
    }
}
