<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Crypto;
use App\Entity\Profile;
use App\Entity\Token\Token;
use App\Exchange\Balance\Model\BalanceResult;
use App\Exchange\Config\Config;
use App\Repository\TokenRepository;
use App\Utils\Converter\String\ParseStringStrategy;
use App\Utils\Converter\String\StringConverter;
use App\Utils\Fetcher\ProfileFetcherInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class TokenManager implements TokenManagerInterface
{
    /** @var TokenRepository */
    private $repository;

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
        $this->repository = $em->getRepository(Token::class);
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
        if (!in_array(
            strtoupper($name),
            array_map(
                function (Crypto $crypto) {
                    return $crypto->getSymbol();
                },
                $this->cryptoManager->findAll()
            )
        )
        ) {
            $name = (new StringConverter(new ParseStringStrategy()))->convert($name);

            $token = $this->repository->findByName($name);

            return $token ?? $this->repository->findByUrl($name);
        }

        return (new Token())->setName(strtoupper($name))->setCrypto(
            $this->cryptoManager->findBySymbol(strtoupper($name))
        );
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

    public function getOwnToken(): ?Token
    {
        $profile = $this->getProfile();

        if (null === $profile) {
            return null;
        }

        return $profile->getToken();
    }

    public function getRealBalance(Token $token, BalanceResult $balanceResult): BalanceResult
    {
        if ($token !== $this->getOwnToken() ||
            $token->getProfile()->getUser() !== $this->getCurrentUser() || !$token->getLockIn()) {
            return $balanceResult;
        }

        return BalanceResult::success(
            $balanceResult->getAvailable()->subtract($token->getLockIn()->getFrozenAmount()),
            $balanceResult->getFreeze()->add($token->getLockIn()->getFrozenAmount()),
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

    private function getProfile(): ?Profile
    {
        return $this->profileFetcher->fetchProfile();
    }

    /** @return mixed */
    private function getCurrentUser()
    {
        $token = $this->storage->getToken();

        return $token
            ? $token->getUser()
            : null;
    }
}
