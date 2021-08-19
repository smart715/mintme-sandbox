<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\Token\DiscordConfig;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DiscordConfigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DiscordConfig::class);
    }
}
