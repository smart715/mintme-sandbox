<?php

namespace App\Manager;

use App\Entity\Crypto;
use App\Entity\Profile;
use App\Entity\Token\Token;
use App\Exchange\Balance\Model\BalanceResult;
use App\Fetcher\ProfileFetcherInterface;
use App\Repository\TokenRepository;
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

    public function __construct(
        EntityManagerInterface $em,
        ProfileFetcherInterface $profileFetcher,
        TokenStorageInterface $storage,
        CryptoManagerInterface $cryptoManager
    ) {
        $this->repository = $em->getRepository(Token::class);
        $this->profileFetcher = $profileFetcher;
        $this->storage = $storage;
        $this->cryptoManager = $cryptoManager;
    }

    public function findByHiddenName(string $name): ?Token
    {
        $id = (int) filter_var($name, FILTER_SANITIZE_NUMBER_INT);

        return $this->repository->find($id);
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
            return $this->repository->findByName($name);
        }

        return (new Token())->setName(strtoupper($name))->setCrypto(
            $this->cryptoManager->findBySymbol(strtoupper($name))
        );
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

    /** {@inheritdoc} */
    public function findAll(): array
    {
        return $this->repository->findAll();
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
        if ($token !== $this->getOwnToken() || $token->getProfile()->getUser() !== $this->getCurrentUser() || !$token->getLockIn()) {
            return $balanceResult;
        }

        return BalanceResult::success(
            $balanceResult->getAvailable()->subtract($token->getLockIn()->getFrozenAmount()),
            $balanceResult->getFreeze()->add($token->getLockIn()->getFrozenAmount())
        );
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

    /** {@inheritdoc} */
    public function getTokensByPattern(string $pattern): array
    {
        return $this->repository->findTokensByPattern($pattern);
    }
}
