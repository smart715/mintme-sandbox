<?php declare(strict_types = 1);

namespace App\Security\Config;

use App\Utils\Converter\RebrandingConverterInterface;
use App\Utils\Symbols;
use Symfony\Component\Serializer\Annotation\Groups;

class DisabledServicesConfig
{
    private RebrandingConverterInterface $rebrandingConverter;
    private bool $coinDepositDisabled;

    private bool $coinWithdrawDisabled;

    private bool $tokenDepositDisabled;

    private bool $tokenWithdrawDisabled;

    private bool $deployDisabled;

    private bool $newTradesDisabled;

    private bool $tradingDisabled;

    private bool $allServicesDisabled;

    private array $blockchainDeployStatus;

    private array $depositsDisabled;

    private array $withdrawalsDisabled;

    private array $tradesDisabled;

    public function __construct(
        RebrandingConverterInterface $rebrandingConverter,
        bool $coinDepositDisabled,
        bool $coinWithdrawDisabled,
        bool $tokenDepositDisabled,
        bool $tokenWithdrawDisabled,
        bool $deployDisabled,
        bool $newTradesDisabled,
        bool $tradingDisabled,
        bool $allServicesDisabled,
        array $blockchainDeployStatus,
        array $depositsDisabled,
        array $withdrawalsDisabled,
        array $tradesDisabled
    ) {
        $this->rebrandingConverter = $rebrandingConverter;
        $this->coinDepositDisabled = $coinDepositDisabled;
        $this->coinWithdrawDisabled = $coinWithdrawDisabled;
        $this->tokenDepositDisabled = $tokenDepositDisabled;
        $this->tokenWithdrawDisabled = $tokenWithdrawDisabled;
        $this->deployDisabled = $deployDisabled;
        $this->newTradesDisabled = $newTradesDisabled;
        $this->tradingDisabled = $tradingDisabled;
        $this->allServicesDisabled = $allServicesDisabled;
        $this->blockchainDeployStatus = $blockchainDeployStatus;
        $this->depositsDisabled = $depositsDisabled;
        $this->withdrawalsDisabled = $withdrawalsDisabled;
        $this->tradesDisabled = $tradesDisabled;
    }

    /**
     * @Groups({"Default"})
     */
    public function isCoinDepositsDisabled(): bool
    {
        return $this->coinDepositDisabled || $this->allServicesDisabled;
    }

    /**
     * @Groups({"Default"})
     */
    public function isCoinWithdrawalsDisabled(): bool
    {
        return $this->coinWithdrawDisabled || $this->allServicesDisabled;
    }

    /**
     * @Groups({"Default"})
     */
    public function isTokenDepositsDisabled(): bool
    {
        return $this->tokenDepositDisabled || $this->allServicesDisabled;
    }

    /**
     * @Groups({"Default"})
     */
    public function isTokenWithdrawalsDisabled(): bool
    {
        return $this->tokenWithdrawDisabled || $this->allServicesDisabled;
    }

    /**
     * @Groups({"Default"})
     */
    public function isDeployDisabled(): bool
    {
        return $this->deployDisabled || $this->allServicesDisabled;
    }

    /**
     * @Groups({"Default"})
     */
    public function isNewTradesDisabled(): bool
    {
        return $this->newTradesDisabled || $this->allServicesDisabled;
    }

    /**
     * @Groups({"Default"})
     */
    public function isTradingDisabled(): bool
    {
        return $this->tradingDisabled || $this->allServicesDisabled;
    }

    /**
     * @Groups({"Default"})
     */
    public function isAllServicesDisabled(): bool
    {
        return $this->allServicesDisabled;
    }

    /**
     * @Groups({"Default"})
     */
    public function getBlockchainDeployStatus(): array
    {
        return $this->blockchainDeployStatus;
    }

    public function getBlockchainDeployStatusByCrypto(string $symbol): bool
    {
        return $this->blockchainDeployStatus[$symbol] ?? false;
    }

    /**
     * @Groups({"Default"})
     */
    public function getAllDeployBlockchains(): array
    {
        return array_map(function (string $symbol) {
            return $this->rebrandingConverter->reverseConvert($symbol);
        }, array_keys($this->getBlockchainDeployStatus()));
    }

    /**
     * @Groups({"Default"})
     */
    public function getDepositsDisabled(): array
    {
        $converted = [];

        foreach ($this->depositsDisabled as $symbol => $value) {
            $converted[$this->rebrandingConverter->reverseConvert($symbol)] = $value;
        }

        return $converted;
    }

    /**
     * @Groups({"Default"})
     */
    public function getWithdrawalsDisabled(): array
    {
        $converted = [];

        foreach ($this->withdrawalsDisabled as $symbol => $value) {
            $converted[$this->rebrandingConverter->reverseConvert($symbol)] = $value;
        }

        return $converted;
    }

    /**
     * @Groups({"Default"})
     */
    public function getTradesDisabled(): array
    {
        $converted = [];

        foreach ($this->tradesDisabled as $symbol => $value) {
            $converted[$this->rebrandingConverter->reverseConvert($symbol)] = $value;
        }

        return $converted;
    }

    public function isCryptoDepositDisabled(string $symbol): bool
    {
        $symbol = $this->rebrandingConverter->convert($symbol);

        return $this->depositsDisabled[$symbol] || $this->coinDepositDisabled || $this->allServicesDisabled;
    }

    public function isCryptoWithdrawalDisabled(string $symbol): bool
    {
        $symbol = $this->rebrandingConverter->convert($symbol);

        return $this->withdrawalsDisabled[$symbol] || $this->coinWithdrawDisabled || $this->allServicesDisabled;
    }

    public function isCryptoTradesDisabled(string $symbol): bool
    {
        $symbol = $this->rebrandingConverter->convert($symbol);

        return $this->tradesDisabled[$symbol] ?? false;
    }
}
