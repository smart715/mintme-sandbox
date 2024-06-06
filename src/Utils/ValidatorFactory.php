<?php declare(strict_types = 1);

namespace App\Utils;

use App\Communications\CryptoRatesFetcherInterface;
use App\Config\MinWithdrawalConfig;
use App\Entity\Crypto;
use App\Entity\TradableInterface;
use App\Exchange\Market;
use App\Services\TranslatorService\TranslatorInterface;
use App\Utils\Converter\RebrandingConverterInterface;
use App\Utils\Validator\AddressValidator;
use App\Utils\Validator\BTCAddressValidator;
use App\Utils\Validator\CustomMinWithdrawalValidator;
use App\Utils\Validator\EthereumAddressValidator;
use App\Utils\Validator\MinAmountValidator;
use App\Utils\Validator\MinTradableValidator;
use App\Utils\Validator\MinUsdValidator;
use App\Utils\Validator\OrderMinUsdValidator;
use App\Utils\Validator\TradableDigitsValidator;
use App\Utils\Validator\ValidatorInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use Money\Money;

/** @codeCoverageIgnore */
class ValidatorFactory implements ValidatorFactoryInterface
{
    public string $minimalPriceOrder;
    public array $fallbackMinCryptoAmount;
    private TranslatorInterface $translator;
    private MoneyWrapperInterface $moneyWrapper;
    private CryptoRatesFetcherInterface $cryptoRatesFetcher;
    private RebrandingConverterInterface $rebranding;
    private MinWithdrawalConfig $minWithdrawalConfig;


    public function __construct(
        TranslatorInterface $translator,
        MoneyWrapperInterface $moneyWrapper,
        CryptoRatesFetcherInterface $cryptoRatesFetcher,
        RebrandingConverterInterface $rebranding,
        MinWithdrawalConfig $minWithdrawalConfig
    ) {
        $this->translator = $translator;
        $this->moneyWrapper = $moneyWrapper;
        $this->cryptoRatesFetcher = $cryptoRatesFetcher;
        $this->rebranding = $rebranding;
        $this->minWithdrawalConfig = $minWithdrawalConfig;
    }

    public function createOrderMinUsdValidator(
        TradableInterface $tradable,
        Money $price,
        Money $amount,
        bool $isBuyOrder,
        array $possibleMatchingSellOrders
    ): ValidatorInterface {
        return new OrderMinUsdValidator(
            $tradable,
            $this->moneyWrapper,
            $this->cryptoRatesFetcher,
            $this->translator,
            $this->rebranding,
            $this->minimalPriceOrder,
            $price,
            $amount,
            $isBuyOrder,
            $possibleMatchingSellOrders
        );
    }

    public function createMinUsdValidator(
        TradableInterface $tradable,
        string $amount,
        ?string $minUsd = null
    ): ValidatorInterface {
        return new MinUsdValidator(
            $tradable,
            $amount,
            $minUsd ?? $this->minimalPriceOrder,
            $this->moneyWrapper,
            $this->cryptoRatesFetcher,
            $this->translator,
            $this->rebranding,
            $this->fallbackMinCryptoAmount
        );
    }

    public function createMinTradableValidator(
        TradableInterface $tradable,
        Market $market,
        string $amount,
        ?string $minimum = null
    ): ValidatorInterface {
        return new MinTradableValidator(
            $tradable,
            $market,
            $amount,
            $minimum,
            $this->moneyWrapper,
            $this->translator,
            $this->rebranding,
        );
    }

    public function createTradableDigitsValidator(
        string $amount,
        TradableInterface $tradable
    ): ValidatorInterface {
        return new TradableDigitsValidator(
            $amount,
            $tradable,
        );
    }

    public function createMinAmountValidator(TradableInterface $tradable, string $amount): ValidatorInterface
    {
        return new MinAmountValidator($tradable, $amount);
    }

    public function createBTCAddressValidator(string $address): ValidatorInterface
    {
        return new BTCAddressValidator($address);
    }

    public function createEthereumAddressValidator(string $address): ValidatorInterface
    {
        return new EthereumAddressValidator($address);
    }

    public function createAddressValidator(Crypto $cryptoNetwork, string $address): ValidatorInterface
    {
        return new AddressValidator($cryptoNetwork, $address);
    }

    public function createCustomMinWithdrawalValidator(Money $amount, TradableInterface $tradable): ValidatorInterface
    {
        return new CustomMinWithdrawalValidator(
            $this->moneyWrapper,
            $this->translator,
            $this->rebranding,
            $this->minWithdrawalConfig,
            $amount,
            $tradable
        );
    }
}
