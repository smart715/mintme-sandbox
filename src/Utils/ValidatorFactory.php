<?php declare(strict_types = 1);

namespace App\Utils;

use App\Communications\CryptoRatesFetcherInterface;
use App\Entity\TradebleInterface;
use App\Exchange\Market;
use App\Utils\Validator\AddressValidator;
use App\Utils\Validator\BTCAddressValidator;
use App\Utils\Validator\EthereumAddressValidator;
use App\Utils\Validator\MinAmountValidator;
use App\Utils\Validator\MinOrderValidator;
use App\Utils\Validator\ValidatorInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/** @codeCoverageIgnore */
class ValidatorFactory implements ValidatorFactoryInterface
{
    public string $minimalPriceOrder;
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function createOrderValidator(
        Market $market,
        string $price,
        string $amount,
        MoneyWrapperInterface $moneyWrapper,
        CryptoRatesFetcherInterface $cryptoRatesFetcher
    ): ValidatorInterface {
        return new MinOrderValidator(
            $market->getBase(),
            $market->getQuote(),
            $price,
            $amount,
            $this->minimalPriceOrder,
            $moneyWrapper,
            $cryptoRatesFetcher,
            $this->translator
        );
    }

    public function createMinAmountValidator(TradebleInterface $tradeble, string $amount): ValidatorInterface
    {
        return new MinAmountValidator($tradeble, $amount);
    }

    public function createBTCAddressValidator(string $address): ValidatorInterface
    {
        return new BTCAddressValidator($address);
    }

    public function createEthereumAddressValidator(string $address): ValidatorInterface
    {
        return new EthereumAddressValidator($address);
    }

    public function createAddressValidator(TradebleInterface $tradeble, string $address): ValidatorInterface
    {
        return new AddressValidator($tradeble, $address);
    }
}
