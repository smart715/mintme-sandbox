<?php declare(strict_types = 1);

namespace App\Entity;

use App\Entity\Token\Token;
use Money\Money;

interface PromotionHistoryInterface
{
    public function getId(): int;

    public function getUser(): User;

    public function getCreatedAt(): int;

    public function getToken(): Token;

    public function getAmount(): Money;

    public function getType(): string;
}
