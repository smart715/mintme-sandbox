<?php declare(strict_types = 1);

namespace App\Manager\PromotionHistory;

use App\Entity\PromotionHistoryInterface;
use App\Entity\User;

interface PromotionHistoryManagerInterface
{
    /** @return PromotionHistoryInterface[] */
    public function getPromotionHistory(
        User $user,
        int $offset,
        int $limit
    ): array;
}
