<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\ValidationCode\ValidationCode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @codeCoverageIgnore */
class ValidationCodeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ValidationCode::class);
    }
}
