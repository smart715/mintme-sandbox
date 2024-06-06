<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Crypto;
use App\Manager\Model\TradableNetworkModel;

interface CryptoManagerInterface
{
    public function findBySymbol(string $symbol, bool $ignoreEnabled = false): ?Crypto;

    /** @return Crypto[] */
    public function findAll(): array;

    /** @return Crypto[] */
    public function findAllAssets(): array;

    public function findAllIndexed(string $index, bool $array = false, bool $onlyEnabled = true): array;

    public function getVotingByCryptoId(int $cryptoId, int $offset, int $limit): array;

    public function getVotingCountAll(): int;

    public function create(
        string $name,
        string $symbol,
        int $subunit,
        int $nativeSubunit,
        int $showSubunit,
        bool $tradable,
        bool $exchangeble,
        bool $isToken = false,
        ?string $fee = null,
        ?Crypto $nativeCoin = null
    ): Crypto;

    public function update(Crypto $crypto): Crypto;

    /** @return TradableNetworkModel[] */
    public function getCryptoNetworks(Crypto $crypto, ?bool $includingDisabled = false): array;

    public function getNetworkName(string $symbol): string;

    public function findSymbolAndSubunitArr(): array;
}
