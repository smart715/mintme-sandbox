<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Token\Token;
use App\Entity\TradableInterface;
use App\Entity\User;
use Money\Money;

interface BonusBalanceTransactionManagerInterface
{
    public function getBalances(User $user): array;
    public function updateBalance(User $user, TradableInterface $token, Money $amount, string $type, string $bonusType): void;
    public function getBalance(User $user, TradableInterface $token): ?Money;
    public function getTransactions(User $user, int $offset, int $limit): array;
}
