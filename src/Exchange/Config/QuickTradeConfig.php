<?php declare(strict_types = 1);

namespace App\Exchange\Config;

use App\Exchange\Market;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use Money\Money;

/** @codeCoverageIgnore */
class QuickTradeConfig
{
    /** @var array<int|float> */
    private array $params;
    private array $minAmounts;
    private MoneyWrapperInterface $moneyWrapper;

    public function __construct(
        array $params,
        array $minAmounts,
        MoneyWrapperInterface $moneyWrapper
    ) {
        $this->params = $params;
        $this->minAmounts = $minAmounts;
        $this->moneyWrapper = $moneyWrapper;
    }

    public function getBuyFeeByMarket(Market $market): string
    {
        if ($market->isTokenMarket()) {
            return $this->getBuyTokenFee();
        }

        return $this->getBuyCryptoFee();
    }

    public function getSellFeeByMarket(Market $market): string
    {
        if ($market->isTokenMarket()) {
            return $this->getSellTokenFee();
        }

        return $this->getSellCryptoFee();
    }

    public function getBuyTokenFee(): string
    {
        return (string)$this->getBuyFees()['token'];
    }

    public function getBuyCryptoFee(): string
    {
        return (string)$this->getBuyFees()['coin'];
    }

    public function getSellTokenFee(): string
    {
        return (string)$this->getSellFees()['token'];
    }

    public function getSellCryptoFee(): string
    {
        return (string)$this->getSellFees()['coin'];
    }


    private function getBuyFees(): array
    {
        return (array)$this->params['buy_fee'];
    }

    private function getSellFees(): array
    {
        return (array)$this->params['sell_fee'];
    }

    public function getMinAmountBySymbol(string $symbol): Money
    {
        return $this->moneyWrapper->parse(
            (string)($this->minAmounts[$symbol] ?? 0),
            $symbol
        );
    }

    public function getDonationParams(): array
    {
        return $this->params;
    }
}
