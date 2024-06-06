<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\PromotionHistoryInterface;
use App\Entity\Token\Token;
use App\Entity\User;

interface PromotionHistoryRepositoryInterface
{
    /**
     * @return PromotionHistoryInterface[]
     */
    public function getPromotionHistoryByUserAndToken(
        User $user,
        int $offset,
        int $limit,
        \DateTimeImmutable $fromDate
    ): array;
}
