<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\DeployTokenReward;
use App\Entity\Profile;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\Model\BalanceResult;
use App\Exchange\Config\Config;
use App\Exchange\Config\TokenConfig;
use App\Manager\Model\TradableNetworkModel;
use App\Repository\DeployNotificationRepository;
use App\Repository\DeployTokenRewardRepository;
use App\Repository\TokenRepository;
use App\Repository\TokenVotingRepository;
use App\Utils\Converter\String\DashStringStrategy;
use App\Utils\Converter\TokenNameConverter;
use App\Utils\Fetcher\ProfileFetcherInterface;
use App\Utils\Symbols;
use Money\Currency;
use Money\Money;

class TokenManager implements TokenManagerInterface
{
    private TokenRepository $tokenRepository;
    private TokenVotingRepository $tokenVotingRepository;
    private DeployTokenRewardRepository $deployTokenRewardRepository;
    private ProfileFetcherInterface $profileFetcher;
    private Config $config;
    private CryptoManager $cryptoManager;
    private TokenConfig $tokenConfig;
    private DashStringStrategy $dashNameConverter;
    private TokenNameConverter $tokenNameConverter;
    private DeployNotificationRepository $deployNotificationRepository;

    public function __construct(
        ProfileFetcherInterface $profileFetcher,
        Config $config,
        TokenRepository $tokenRepository,
        TokenVotingRepository $tokenVotingRepository,
        DeployTokenRewardRepository $deployTokenRewardRepository,
        DashStringStrategy $dashNameConverter,
        CryptoManager $cryptoManager,
        TokenConfig $tokenConfig,
        TokenNameConverter $tokenNameConverter,
        DeployNotificationRepository $deployNotificationRepository
    ) {
        $this->tokenRepository = $tokenRepository;
        $this->tokenVotingRepository = $tokenVotingRepository;
        $this->deployTokenRewardRepository = $deployTokenRewardRepository;
        $this->profileFetcher = $profileFetcher;
        $this->config = $config;
        $this->dashNameConverter = $dashNameConverter;
        $this->cryptoManager = $cryptoManager;
        $this->tokenConfig = $tokenConfig;
        $this->tokenNameConverter = $tokenNameConverter;
        $this->deployNotificationRepository = $deployNotificationRepository;
    }

    public function getRepository(): TokenRepository
    {
        return $this->tokenRepository;
    }

    public function findByHiddenName(string $name): ?Token
    {
        $id = (int)filter_var($name, FILTER_SANITIZE_NUMBER_INT);

        return $this->tokenRepository->find($id - $this->config->getOffset());
    }

    public function findByConvertedIds(array $tokenKeys): array
    {
        $ids = array_map(fn(string $tokenKey) => $this->tokenNameConverter->parseConvertedId($tokenKey), $tokenKeys);
        $tokens = $this->tokenRepository->findByIdsWithDeploys($ids);

        $tokenKeysPointingToTokens = [];

        foreach ($tokens as $token) {
            $tokenKeysPointingToTokens[$this->tokenNameConverter->convert($token)] = $token;
        }

        return $tokenKeysPointingToTokens;
    }

    public function findByName(string $name): ?Token
    {
        $name = $this->dashNameConverter->convert($name);

        return $this->tokenRepository->findByName($name) ?? $this->tokenRepository->findByUrl($name);
    }

    /** {@inheritdoc} */
    public function findByUrl(string $name): ?Token
    {
        return $this->tokenRepository->findByUrl($name);
    }

    public function findById(int $id): ?Token
    {
        return $this->tokenRepository->find($id);
    }

    public function getRandomTokens(int $limit): array
    {
        return $this->tokenRepository->getRandomTokens($limit);
    }

    public function findByNameCrypto(string $name, string $cryptoSymbol): ?Token
    {
        $token = $this->findByName($name);

        return $token && $token->getCryptoSymbol() === $cryptoSymbol
            ? $token
            : null;
    }

    public function findByNameMintme(string $name): ?Token
    {
        return $this->findByNameCrypto($name, Symbols::WEB);
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public function getTokensByPattern(string $pattern): array
    {
        return $this->tokenRepository->findTokensByPattern($pattern);
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public function findAll(?int $offset = null, ?int $limit = null): array
    {
        return $this->tokenRepository->findBy([], null, $limit, $offset);
    }

    public function getOwnMintmeToken(): ?Token
    {
        return $this->getProfile()
            ? $this->getProfile()->getMintmeToken()
            : null;
    }

    public function getOwnTokenByName(string $name): ?Token
    {
        $name = $this->dashNameConverter->convert($name);

        /** @var Token $ownToken */
        foreach ($this->getOwnTokens() as $ownToken) {
            $tmpName = $this->dashNameConverter->convert($ownToken->getName());

            if ($name === $tmpName) {
                return $ownToken;
            }
        }

        return null;
    }

    public function getOwnTokens(): array
    {
        return $this->getProfile()
            ? $this->getProfile()->getTokens()
            : [];
    }

    public function getOwnDeployedTokens(): array
    {
        return array_filter($this->getOwnTokens(), fn(Token $token) => $token->isDeployed());
    }

    public function getTokensCount(): int
    {
        return count(
            array_filter(
                $this->getOwnTokens(),
                static fn(Token $token) => !$token->isBlocked() && !$token->isHidden()
            )
        );
    }

    public function getRealBalance(Token $token, BalanceResult $balanceResult, User $user): BalanceResult
    {
        $isOwner = $token->isOwner($user->getProfile()->getTokens());

        if (!$isOwner
            || $token->getProfile()->getUser() !== $user
            || !$token->getLockIn()
        ) {
            return $balanceResult;
        }

        $available = $balanceResult->getAvailable();
        $available = $token->isDeployed()
            ? $available->subtract($token->getLockIn()->getFrozenAmountWithReceived())
            : $available->subtract($token->getLockIn()->getFrozenAmount());

        $freeze = $balanceResult->getFreeze();
        $freeze = $token->isDeployed()
            ? $freeze->add($token->getLockIn()->getFrozenAmountWithReceived())
            : $freeze->add($token->getLockIn()->getFrozenAmount());

        return BalanceResult::success(
            $available,
            $freeze,
            $balanceResult->getReferral()
        );
    }

    public function isExisted(string $tokenName): bool
    {
        $tokenName = strtoupper($tokenName);

        $toDashedTokenName = str_replace(' ', '-', $tokenName);
        $toDashedToken = $this->findByName($toDashedTokenName);

        if (null !== $toDashedToken && $tokenName !== $toDashedTokenName) {
            return true;
        }

        $toSpaceTokenName = str_replace('-', ' ', $tokenName);
        $toSpaceToken = $this->findByName($toSpaceTokenName);

        return null !== $toSpaceToken && $tokenName !== $toSpaceTokenName;
    }

    public function getDeployedTokens(?int $offset = null, ?int $limit = null): array
    {
        return $this->tokenRepository->getDeployedTokens($offset, $limit);
    }

    /** {@inheritdoc} */
    public function getUserAllDeployTokensReward(User $user, array $cryptoValues): array
    {
        $allDeployTokensReward = [];

        foreach ($cryptoValues as $crypto) {
            $rewardZero = new Money(0, new Currency($crypto->getSymbol()));
            $rewards = $this->deployTokenRewardRepository->findBy([
                'user' => $user,
                'currency' => $crypto->getSymbol(),
            ]);

            $allDeployTokensReward[$crypto->getSymbol()] = array_reduce($rewards, function (
                Money $sum,
                DeployTokenReward $deployTokenReward
            ) {
                return $deployTokenReward->getReward()->add($sum);
            }, $rewardZero);
        }

        return $allDeployTokensReward;
    }

    /** {@inheritdoc} */
    public function findAllTokensWithEmptyDescription(int $param = 14): array
    {
        return $this->tokenRepository->findAllTokensWithEmptyDescription($param);
    }

    public function getTokensWithoutAirdrops(): array
    {
        return $this->tokenRepository->getTokensWithoutAirdrops();
    }

    public function getTokensWithAirdrops(): array
    {
        return $this->tokenRepository->getTokensWithAirdrops();
    }

    public function getNotOwnTokens(User $user): array
    {
        return $this->tokenRepository->getNotOwnTokens($user);
    }

    private function getProfile(): ?Profile
    {
        return $this->profileFetcher->fetchProfile();
    }

    public function getVotingByTokenId(int $tokenId, int $offset, int $limit): array
    {
        return $this->tokenVotingRepository->getVotingByTokenId($tokenId, $offset, $limit) ?? [];
    }

    public function getTokenNetworks(Token $token): array
    {
        $networks = [];

        foreach ($token->getDeploys() as $tokenDeploy) {
            if ($tokenDeploy->isPending()) {
                continue;
            }

            $tokenNetworkFee = $token->getFee()
                ?? $this->tokenConfig->getWithdrawFeeByCryptoSymbol($tokenDeploy->getCrypto()->getMoneySymbol())
                ?? $tokenDeploy->getCrypto()->getFee();

            if (!$tokenNetworkFee) {
                continue;
            }

            $networks[] = new TradableNetworkModel(
                $this->cryptoManager->getNetworkName($tokenDeploy->getCrypto()->getSymbol()),
                $tokenDeploy->getCrypto(),
                $tokenNetworkFee,
                $tokenDeploy->getCrypto()->getShowSubunit(),
                $tokenDeploy->getAddress(),
            );
        }

        return $networks;
    }

    /**
     * @param User[] $users
     */
    public function findNotNotifiedByUsersNotDeployedToken(array $users, int $maxNotifications): ?Token
    {
        $alreadyNotifiedTokenIds = $this->deployNotificationRepository
            ->getAlreadyNotifiedByUsersTokenIDs($users, $maxNotifications);

        return $this->tokenRepository->findNotDeployedRandomTokenWithExcludedIDs($alreadyNotifiedTokenIds);
    }
}
