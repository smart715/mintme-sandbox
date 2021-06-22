<?php declare(strict_types = 1);

namespace App\Exchange\Config;

use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use Money\Money;

/** @codeCoverageIgnore */
class QuickTradeConfig
{
    /** @var array<int|float> */
    private $params;

    /** @var MoneyWrapperInterface */
    private $moneyWrapper;

    public function __construct(array $params, MoneyWrapperInterface $moneyWrapper)
    {
        $this->params = $params;
        $this->moneyWrapper = $moneyWrapper;
    }

    public function getDonationFee(): string
    {
        $fee = $this->params['donation_fee'] ?? 0;

        return (string)$fee;
    }

    public function getSellFee(): string
    {
        $fee = $this->params['sell_fee'] ?? 0;

        return (string)$fee;
    }

    public function getMinBtcAmount(): Money
    {
        return $this->moneyWrapper->parse(
            (string)($this->params['minBtcAmount'] ?? 0),
            Symbols::BTC
        );
    }

    public function getMinMintmeAmount(): Money
    {
        return $this->moneyWrapper->parse(
            (string)($this->params['minMintmeAmount'] ?? 0),
            Symbols::WEB
        );
    }

    public function getMinEthAmount(): Money
    {
        return $this->moneyWrapper->parse(
            (string)($this->params['minEthAmount'] ?? 0),
            Symbols::ETH
        );
    }

    public function getMinUsdcAmount(): Money
    {
        return $this->moneyWrapper->parse(
            (string)($this->params['minUsdcAmount'] ?? 0),
            Symbols::USDC
        );
    }

    public function getMinBnbAmount(): Money
    {
        return $this->moneyWrapper->parse(
            (string)($this->params['minBnbAmount'] ?? 0),
            Symbols::BNB
        );
    }

    public function getMinTokensAmount(): Money
    {
        return $this->moneyWrapper->parse(
            (string)($this->params['minTokensAmount'] ?? 0),
            Symbols::WEB
        );
    }

    public function getDonationParams(): array
    {
        return $this->params;
    }
}
