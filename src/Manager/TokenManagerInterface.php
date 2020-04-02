<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\Model\BalanceResult;

interface TokenManagerInterface
{
    public function findByName(string $name): ?Token;

    public function findByAddress(string $address): ?Token;

    public function getOwnToken(): ?Token;

    public function findByHiddenName(string $name): ?Token;

    /** @return Token[] */
    public function findAll(?int $offset = null, ?int $limit = null): array;

    /** @return Token[] */
    public function findAllPredefined(): array;

    public function isPredefined(Token $token): bool;

    public function getRealBalance(Token $token, BalanceResult $balanceResult): BalanceResult;

    /** @return Token[] */
    public function getTokensByPattern(string $pattern): array;

    public function isExisted(string $tokenName): bool;
}
