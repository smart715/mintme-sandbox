<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\PendingWithdraw;
use Doctrine\Common\Persistence\ManagerRegistry;

class PendingWithdrawRepository extends AbstractPendingWithdrawRepository
{
    public function __construct(
        ManagerRegistry $registry,
        int $expirationTime
    ) {
        parent::__construct($registry, PendingWithdraw::class, $expirationTime);
    }
}
