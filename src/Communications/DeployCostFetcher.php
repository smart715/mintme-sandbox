<?php declare(strict_types = 1);

namespace App\Communications;

use App\Communications\Exception\FetchException;
use App\Config\HideFeaturesConfig;
use App\Exchange\Config\DeployCostConfig;
use App\Manager\CryptoManagerInterface;
use App\Security\Config\DisabledServicesConfig;
use App\Utils\Converter\RebrandingConverterInterface;
use App\Utils\CryptoCalculator;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use Money\Currency;
use Money\Money;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;

class DeployCostFetcher extends AbstractCostFetcher implements DeployCostFetcherInterface
{
    private DeployCostConfig $config;
    private CryptoCalculator $cryptoCalculator;

    public function __construct(
        DeployCostConfig $config,
        CryptoCalculator $cryptoCalculator,
        RestRpcInterface $rpc,
        MoneyWrapperInterface $moneyWrapper,
        CryptoManagerInterface $cryptoManager,
        CacheInterface $cache,
        DisabledServicesConfig $disabledServicesConfig,
        ExternalServiceIdsMapperInterface $cryptoIdsMapper,
        HideFeaturesConfig $hideFeaturesConfig,
        LoggerInterface $logger,
        RebrandingConverterInterface $rebrandingConverter
    ) {
        $this->config = $config;
        $this->cryptoCalculator = $cryptoCalculator;

        parent::__construct(
            $rpc,
            $moneyWrapper,
            $cache,
            $cryptoManager,
            $disabledServicesConfig,
            $cryptoIdsMapper,
            $hideFeaturesConfig,
            $logger,
            $rebrandingConverter
        );
    }

    public function getCost(string $symbol): Money
    {
        $prices = $this->fetchPrices();
        $crypto = $this->cryptoManager->findBySymbol($symbol);

        if (null === $crypto) {
            throw new \RuntimeException('Crypto not found');
        }

        $cost = $this->rate(
            (string)$this->config->getDeployCost($crypto->getSymbol()),
            $crypto->getMoneySymbol(),
            $prices
        );

        $fee = $this->moneyWrapper->parse(
            (string)$this->config->getDeployFee($symbol),
            $crypto->getMoneySymbol(),
        );

        return $cost->add($fee);
    }

    public function getCosts(): array
    {
        $prices = $this->fetchPrices();

        $costs = [];

        foreach ($this->cryptos as $crypto) {
            $cost = $this->rate(
                (string)$this->config->getDeployCost($crypto->getSymbol()),
                $crypto->getMoneySymbol(),
                $prices
            );

            $fee = $this->moneyWrapper->parse(
                (string)$this->config->getDeployFee($crypto->getSymbol()),
                $crypto->getMoneySymbol(),
            );

            $costs[$crypto->getSymbol()] = $cost->add($fee);
        }

        return $costs;
    }

    /**
     * @return Money
     * @throws FetchException
     */
    public function getDeployCostReferralReward(string $symbol): Money
    {
        $deployCostRewardMultiplier = $this->config->getDeployCostReward($symbol);
        $deployCost = $this->getCost($symbol);

        if ($deployCostRewardMultiplier <= 0 || !$deployCost->isPositive()) {
            return new Money(0, new Currency(Symbols::WEB));
        }

        $deployCostReferralReward = $deployCost->multiply($deployCostRewardMultiplier);

        if (Symbols::WEB !== $symbol) {
            return $this->cryptoCalculator->getMintmeWorth($deployCostReferralReward);
        }

        return $deployCostReferralReward;
    }
}
