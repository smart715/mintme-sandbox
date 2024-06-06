<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Crypto;
use App\Entity\WrappedCryptoToken;
use App\Repository\WrappedCryptoTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Money\Money;

class WrappedCryptoTokenManager implements WrappedCryptoTokenManagerInterface
{
    private EntityManagerInterface $entityManager;
    private WrappedCryptoTokenRepository $repository;

    public function __construct(EntityManagerInterface $entityManager, WrappedCryptoTokenRepository $repository)
    {
        $this->entityManager = $entityManager;
        $this->repository = $repository;
    }

    public function create(
        Crypto $crypto,
        Crypto $cryptoDeploy,
        ?string $address,
        Money $fee
    ): WrappedCryptoToken {
        if ($fee->getCurrency()->getCode() !== $crypto->getMoneySymbol()) {
            throw new \Exception('WrappedCryptoToken fee must be in crypto currency');
        }

        $wrappedCrypto = new WrappedCryptoToken($crypto, $cryptoDeploy, $address, $fee);

        $this->entityManager->persist($wrappedCrypto);
        $this->entityManager->flush();

        return $wrappedCrypto;
    }

    public function update(WrappedCryptoToken $wct): WrappedCryptoToken
    {
        $this->entityManager->persist($wct);
        $this->entityManager->flush();

        return $wct;
    }

    public function findByCryptoAndDeploy(Crypto $crypto, Crypto $cryptoDeploy): ?WrappedCryptoToken
    {
        return $this->repository->findByCryptoAndDeploy($crypto, $cryptoDeploy);
    }

    public function updateWrappedCryptoTokenStatus(WrappedCryptoToken $wrappedCryptoToken, bool $status): void
    {
        $wrappedCryptoToken = $wrappedCryptoToken->setEnabled($status);

        $this->update($wrappedCryptoToken);
    }

    public function updateCryptoStatuses(Crypto $crypto, bool $status): void
    {
        $this->repository->createQueryBuilder('wct')
            ->update()
            ->set('wct.enabled', ':status')
            ->where('wct.crypto = :crypto')
            ->setParameter('status', $status)
            ->setParameter('crypto', $crypto)
            ->getQuery()
            ->execute();
    }

    public function findNativeBlockchainCrypto(Crypto $cryptoDeploy): ?WrappedCryptoToken
    {
        return $this->repository->findNativeBlockchainCrypto($cryptoDeploy);
    }
}
