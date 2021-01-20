<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\Model\BalanceResult;
use Money\Money;

interface TokenManagerInterface
{
    public function findByName(string $name): ?Token;

    public function findByNameCrypto(string $name, string $cryptoSymbol): ?Token;

    public function findByNameMintme(string $name): ?Token;

    public function findByAddress(string $address): ?Token;

    public function getOwnMintmeToken(): ?Token;

    public function getOwnTokenByName(string $name): ?Token;

    public function getOwnTokens(): array;

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

    /** @return Token[] */
    public function getDeployedTokens(?int $offset = null, ?int $limit = null): array;

    public function getUserDeployTokensReward(User $user): Money;
}
