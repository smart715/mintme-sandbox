<?php declare(strict_types = 1);

namespace App\Communications;

use App\Config\HideFeaturesConfig;
use App\Exchange\Config\ConnectCostConfig;
use App\Manager\CryptoManagerInterface;
use App\Security\Config\DisabledServicesConfig;
use App\Utils\Converter\RebrandingConverterInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use Money\Money;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;

final class ConnectCostFetcher extends AbstractCostFetcher implements ConnectCostFetcherInterface
{
    private ConnectCostConfig $config;

    public function __construct(
        RestRpcInterface $rpc,
        ConnectCostConfig $config,
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
            (string)$this->config->getConnectCost($crypto->getSymbol()),
            $crypto->getMoneySymbol(),
            $prices
        );

        $fee = $this->moneyWrapper->parse(
            (string)$this->config->getConnectFee($symbol),
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
                (string)$this->config->getConnectCost($crypto->getSymbol()),
                $crypto->getMoneySymbol(),
                $prices
            );

            $fee = $this->moneyWrapper->parse(
                (string)$this->config->getConnectFee($crypto->getSymbol()),
                $crypto->getMoneySymbol(),
            );

            $costs[$crypto->getSymbol()] = $cost->add($fee);
        }

        return $costs;
    }
}
