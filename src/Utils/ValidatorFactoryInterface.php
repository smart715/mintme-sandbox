<?php declare(strict_types = 1);

namespace App\Utils;

use App\Entity\Crypto;
use App\Entity\TradableInterface;
use App\Exchange\Market;
use App\Utils\Validator\ValidatorInterface;
use Money\Money;

interface ValidatorFactoryInterface
{
    public function createOrderMinUsdValidator(
        TradableInterface $tradable,
        Money $price,
        Money $amount,
        bool $isBuyOrder,
        array $possibleMatchingSellOrders
    ): ValidatorInterface;

    public function createMinUsdValidator(
        TradableInterface $tradable,
        string $amount,
        ?string $minUsd = null
    ): ValidatorInterface;

    public function createMinTradableValidator(
        TradableInterface $tradable,
        Market $market,
        string $amount,
        ?string $minimum = null
    ): ValidatorInterface;

    public function createTradableDigitsValidator(
        string $amount,
        TradableInterface $tradable
    ): ValidatorInterface;

    public function createMinAmountValidator(TradableInterface $tradable, string $amount): ValidatorInterface;

    public function createBTCAddressValidator(string $address): ValidatorInterface;

    public function createEthereumAddressValidator(string $address): ValidatorInterface;

    public function createAddressValidator(Crypto $cryptoNetwork, string $address): ValidatorInterface;

    public function createCustomMinWithdrawalValidator(Money $amount, TradableInterface $tradable): ValidatorInterface;
}
