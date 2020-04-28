<?php declare(strict_types = 1);

namespace App\Consumers\Helpers;

use Doctrine\ORM\EntityManagerInterface;

class DBConnection
{
    public static function reconnectIfDisconnected(EntityManagerInterface &$em): void
    {
        if (false === $em->getConnection()->ping()) {
            $em->getConnection()->close();
            $em->getConnection()->connect();
        }
    }
}
