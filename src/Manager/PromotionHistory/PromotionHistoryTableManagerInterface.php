<?php declare(strict_types = 1);

namespace App\Manager\PromotionHistory;

use App\Entity\PromotionHistoryInterface;

interface PromotionHistoryTableManagerInterface
{
    public function getCurrentElement(): ?PromotionHistoryInterface;

    public function nextElement(): void;
}
