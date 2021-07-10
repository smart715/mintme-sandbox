<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\DeployTokenReward;
use App\Entity\Profile;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\Model\BalanceResult;
use App\Exchange\Config\Config;
use App\Repository\DeployTokenRewardRepository;
use App\Repository\TokenRepository;
use App\Utils\Converter\String\ParseStringStrategy;
use App\Utils\Converter\String\StringConverter;
use App\Utils\Fetcher\ProfileFetcherInterface;
use App\Utils\Symbols;
use Money\Currency;
use Money\Money;

class TokenManager implements TokenManagerInterface
{
    /** @var TokenRepository */
    private $repository;

    /** @var DeployTokenRewardRepository */
    private $deployTokenRewardRepository;

    /** @var ProfileFetcherInterface */
    private $profileFetcher;

    /** @var TokenStorageInterface */
    private $storage;

    /** @var Config */
    private $config;
    private TokenRepository $tokenRepository;
    private DeployTokenRewardRepository $deployTokenRewardRepository;
    private ProfileFetcherInterface $profileFetcher;
    private Config $config;

    public function __construct(
        ProfileFetcherInterface $profileFetcher,
        TokenStorageInterface $storage,
        Config $config
        TokenRepository $tokenRepository,
        DeployTokenRewardRepository $deployTokenRewardRepository
    ) {
        $this->tokenRepository = $tokenRepository;
        $this->deployTokenRewardRepository = $deployTokenRewardRepository;
        $this->profileFetcher = $profileFetcher;
        $this->storage = $storage;
        $this->config = $config;
    }

    public function findByHiddenName(string $name): ?Token
    {
        $id = (int)filter_var($name, FILTER_SANITIZE_NUMBER_INT);

        return $this->tokenRepository->find($id - $this->config->getOffset());
    }

    public function findByName(string $name): ?Token
    {
        $name = strtoupper($name);

        $name = (new StringConverter(new ParseStringStrategy()))->convert($name);

        return $this->repository->findByName($name);
    }

    public function findById(int $id): ?Token
    {
        return $this->tokenRepository->find($id);
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

    public function findByAddress(string $address): ?Token
    {
        return $this->tokenRepository->findByAddress($address);
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
        /** @var Token $ownToken */
        foreach ($this->getOwnTokens() as $ownToken) {
            if ($name === $ownToken->getName()) {
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

    public function getUserDeployTokensReward(User $user): Money
    {
        $rewardZero = new Money(0, new Currency(Symbols::WEB));
        $rewards = $this->deployTokenRewardRepository->findBy([
            'user' => $user,
        ]);

        return array_reduce($rewards, function (Money $sum, DeployTokenReward $deployTokenReward) {
            return $deployTokenReward->getReward()->add($sum);
        }, $rewardZero);
    }

    public function findAllTokensWithEmptyDescription(int $param = 14): ?array
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

    private function getProfile(): ?Profile
    {
        return $this->profileFetcher->fetchProfile();
    }
}
