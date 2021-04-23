<?php declare(strict_types = 1);

namespace App\Utils;

use App\Communications\CryptoRatesFetcherInterface;
use App\Entity\TradebleInterface;
use App\Exchange\Market;
use App\Utils\Validator\ValidatorInterface;
use App\Wallet\Money\MoneyWrapperInterface;

interface ValidatorFactoryInterface
{
    public function createOrderValidator(
        Market $market,
        string $price,
        string $amount
    ): ValidatorInterface;
    public function createMinAmountValidator(TradebleInterface $tradeble, string $amount): ValidatorInterface;
    public function createBTCAddressValidator(string $address): ValidatorInterface;
    public function createEthereumAddressValidator(string $address): ValidatorInterface;
    public function createAddressValidator(TradebleInterface $tradeble, string $address): ValidatorInterface;
}
