<?php declare(strict_types = 1);

namespace App\Exchange\Config;

use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use Money\Money;

/** @codeCoverageIgnore */
class DonationConfig
{
    /** @var array<int|float> */
    private array $donationParams;
    private MoneyWrapperInterface $moneyWrapper;

    public function __construct(array $donationParams, MoneyWrapperInterface $moneyWrapper)
    {
        $this->donationParams = $donationParams;
        $this->moneyWrapper = $moneyWrapper;
    }

    public function getFee(): string
    {
        $fee = $this->donationParams['fee'] ?? 0;

        return (string)($fee / 100);
    }

    public function getMinBtcAmount(): Money
    {
        return $this->moneyWrapper->parse(
            (string)($this->donationParams['minBtcAmount'] ?? 0),
            Symbols::BTC
        );
    }

    public function getMinMintmeAmount(): Money
    {
        return $this->moneyWrapper->parse(
            (string)($this->donationParams['minMintmeAmount'] ?? 0),
            Symbols::WEB
        );
    }

    public function getMinEthAmount(): Money
    {
        return $this->moneyWrapper->parse(
            (string)($this->donationParams['minEthAmount'] ?? 0),
            Symbols::ETH
        );
    }

    public function getMinUsdcAmount(): Money
    {
        return $this->moneyWrapper->parse(
            (string)($this->donationParams['minUsdcAmount'] ?? 0),
            Symbols::USDC
        );
    }

    public function getMinBnbAmount(): Money
    {
        return $this->moneyWrapper->parse(
            (string)($this->donationParams['minBnbAmount'] ?? 0),
            Symbols::BNB
        );
    }

    public function getMinTokensAmount(): Money
    {
        return $this->moneyWrapper->parse(
            (string)($this->donationParams['minTokensAmount'] ?? 0),
            Symbols::WEB
        );
    }

    public function getDonationParams(): array
    {
        return $this->donationParams;
    }
}
