<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\Model\BalanceResult;
use App\Manager\Model\TradableNetworkModel;
use App\Repository\TokenRepository;
use Money\Money;

interface TokenManagerInterface
{
    public function getRepository(): TokenRepository;

    public function findByName(string $name): ?Token;

    public function findById(int $id): ?Token;

    public function findByUrl(string $name): ?Token;

    public function findByNameCrypto(string $name, string $cryptoSymbol): ?Token;

    public function findByNameMintme(string $name): ?Token;

    public function getOwnMintmeToken(): ?Token;

    public function getOwnTokenByName(string $name): ?Token;

    /** @return Token[] */
    public function getOwnTokens(): array;

    /** @return Token[] */
    public function getOwnDeployedTokens(): array;

    public function findByConvertedIds(array $tokenKeys): array;

    public function getTokensCount(): int;

    public function findByHiddenName(string $name): ?Token;

    /** @return Token[] */
    public function findAll(?int $offset = null, ?int $limit = null): array;

    public function getRealBalance(Token $token, BalanceResult $balanceResult, User $user): BalanceResult;

    /** @return Token[] */
    public function getTokensByPattern(string $pattern): array;

    public function isExisted(string $tokenName): bool;

    /** @return Token[] */
    public function getDeployedTokens(?int $offset = null, ?int $limit = null): array;

    /** @return Token[] */
    public function getRandomTokens(int $limit): array;

    /**
     * @param User $user
     * @param array $cryptoValues
     * @return Money[]
     */
    public function getUserAllDeployTokensReward(User $user, array $cryptoValues): array;

    /** @return Token[] */
    public function getTokensWithoutAirdrops(): array;

    /** @return Token[] */
    public function getTokensWithAirdrops(): array;

    /** @return Token[] */
    public function getNotOwnTokens(User $user): array;

    public function getVotingByTokenId(int $tokenId, int $offset, int $limit): array;

    /** @return TradableNetworkModel[] */
    public function getTokenNetworks(Token $token): array;

    /** @return Token[] */
    public function findAllTokensWithEmptyDescription(int $param = 14): array;

    /**
     * @param User[] $users
     */
    public function findNotNotifiedByUsersNotDeployedToken(array $users, int $maxNotifications): ?Token;
}
