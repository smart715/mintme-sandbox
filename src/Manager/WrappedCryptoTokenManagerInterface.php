<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Crypto;
use App\Entity\WrappedCryptoToken;
use Money\Money;

interface WrappedCryptoTokenManagerInterface
{
    public function create(
        Crypto $crypto,
        Crypto $cryptoDeploy,
        ?string $address,
        Money $fee
    ): WrappedCryptoToken;

    public function findByCryptoAndDeploy(Crypto $crypto, Crypto $cryptoDeploy): ?WrappedCryptoToken;

    public function updateWrappedCryptoTokenStatus(WrappedCryptoToken $wrappedCryptoToken, bool $status): void;

    public function updateCryptoStatuses(Crypto $crypto, bool $status): void;

    public function findNativeBlockchainCrypto(Crypto $cryptoDeploy): ?WrappedCryptoToken;
}
