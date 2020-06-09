<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\PendingTokenWithdraw;
use Doctrine\Common\Persistence\ManagerRegistry;

class PendingTokenWithdrawRepository extends AbstractPendingWithdrawRepository
{
    public function __construct(
        ManagerRegistry $registry,
        int $expirationTime
    ) {
        parent::__construct($registry, PendingTokenWithdraw::class, $expirationTime);
    }
}
