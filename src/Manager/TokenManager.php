<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Crypto;
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
use Doctrine\ORM\EntityManagerInterface;
use Money\Currency;
use Money\Money;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

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

    /** @var CryptoManagerInterface */
    private $cryptoManager;

    /** @var Config */
    private $config;

    public function __construct(
        EntityManagerInterface $em,
        ProfileFetcherInterface $profileFetcher,
        TokenStorageInterface $storage,
        CryptoManagerInterface $cryptoManager,
        Config $config
    ) {
        /** @var TokenRepository $repository */
        $repository = $em->getRepository(Token::class);
        $this->repository = $repository;
        /** @var DeployTokenRewardRepository $deployTokenRewardRepository */
        $deployTokenRewardRepository = $em->getRepository(DeployTokenReward::class);
        $this->deployTokenRewardRepository = $deployTokenRewardRepository;
        $this->profileFetcher = $profileFetcher;
        $this->storage = $storage;
        $this->cryptoManager = $cryptoManager;
        $this->config = $config;
    }

    public function findByHiddenName(string $name): ?Token
    {
        $id = (int)filter_var($name, FILTER_SANITIZE_NUMBER_INT);

        return $this->repository->find($id - $this->config->getOffset());
    }

    public function findByName(string $name): ?Token
    {
        $name = Token::MINTME_SYMBOL === strtoupper($name)
            ? Token::WEB_SYMBOL
            : strtoupper($name);

        if (!in_array(strtoupper($name), array_map(
            static function (Crypto $crypto) {
                return $crypto->getSymbol();
            },
            $this->cryptoManager->findAll()
        ), true)
        ) {
            $name = (new StringConverter(new ParseStringStrategy()))->convert($name);

            $token = $this->repository->findByName($name);

            return $token ?? $this->repository->findByUrl($name);
        }

        return (new Token())->setName(strtoupper($name))->setCrypto(
            $this->cryptoManager->findBySymbol(strtoupper($name))
        );
    }

    public function findByNameCrypto(string $name, string $cryptoSymbol): ?Token
    {
        $token = $this->findByName($name);

        return $token->getCryptoSymbol() === $cryptoSymbol
            ? $token
            : null;
    }

    public function findByNameMintme(string $name): ?Token
    {
        return $this->findByNameCrypto($name, Token::WEB_SYMBOL);
    }

    public function findByAddress(string $address): ?Token
    {
        return $this->repository->findByAddress($address);
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public function getTokensByPattern(string $pattern): array
    {
        return $this->repository->findTokensByPattern($pattern);
    }

    /** {@inheritdoc} */
    public function findAllPredefined(): array
    {
        return array_map(
            function (Crypto $crypto) {
                return Token::getFromCrypto($crypto)->setCrypto($crypto);
            },
            $this->cryptoManager->findAll()
        );
    }

    public function isPredefined(Token $token): bool
    {
        return in_array($token, $this->findAllPredefined());
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public function findAll(?int $offset = null, ?int $limit = null): array
    {
        return $this->repository->findBy([], null, $limit, $offset);
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

    public function getRealBalance(Token $token, BalanceResult $balanceResult): BalanceResult
    {
        if (!$token->isOwner($this->getOwnTokens()) ||
            $token->getProfile()->getUser() !== $this->getCurrentUser() || !$token->getLockIn()) {
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
        return $this->repository->getDeployedTokens($offset, $limit);
    }

    public function getUserDeployTokensReward(User $user): Money
    {
        $rewardZero = new Money(0, new Currency(Token::WEB_SYMBOL));
        $rewards = $this->deployTokenRewardRepository->findBy([
            'user' => $user,
        ]);

        return array_reduce($rewards, function (Money $sum, DeployTokenReward $deployTokenReward) {
            return $deployTokenReward->getReward()->add($sum);
        }, $rewardZero);
    }

    public function findAllTokensWithEmptyDescription(int $param = 14): ?array
    {
        return $this->repository->findAllTokensWithEmptyDescription($param);
    }

    public function getTokensWithoutAirdrops(): array
    {
        return $this->repository->getTokensWithoutAirdrops();
    }

    public function getTokensWithAirdrops(): array
    {
        return $this->repository->getTokensWithAirdrops();
    }

    private function getProfile(): ?Profile
    {
        return $this->profileFetcher->fetchProfile();
    }

    /** @return mixed */
    private function getCurrentUser()
    {
        $token = $this->storage->getToken();

        /** @psalm-suppress UndefinedDocblockClass */
        return $token
            ? $token->getUser()
            : null;
    }
}
