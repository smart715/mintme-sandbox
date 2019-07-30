<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\Model\BalanceResult;

interface TokenManagerInterface
{
    public function findByName(string $name): ?Token;

    public function getOwnToken(): ?Token;

    public function findByHiddenName(string $name): ?Token;

    /** @return Token[] */
    public function findAll(): array;

    /** @return Token[] */
    public function findAllPredefined(): array;

    public function getRealBalance(Token $token, BalanceResult $balanceResult): BalanceResult;

    /** @return Token[] */
    public function getTokensByPattern(string $pattern): array;

    public function isExisted(string $tokenName): bool;
}
