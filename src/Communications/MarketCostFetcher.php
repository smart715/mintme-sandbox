<?php declare(strict_types = 1);

namespace App\Communications;

use App\Communications\Exception\FetchException;
use App\Config\HideFeaturesConfig;
use App\Entity\Crypto;
use App\Exchange\Config\TokenMarketConfig;
use App\Manager\CryptoManagerInterface;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use Money\Currency;
use Money\Exchange\FixedExchange;
use Money\Money;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

class MarketCostFetcher implements MarketCostFetcherInterface
{
    private TokenMarketConfig $marketConfig;
    private MoneyWrapperInterface $moneyWrapper;
    private RestRpcInterface $rpc;
    private HideFeaturesConfig $hideFeaturesConfig;
    private ExternalServiceIdsMapperInterface $cryptoIdsMapper;
    private array $enabledCryptosIds;

    private LoggerInterface $logger;

    public function __construct(
        TokenMarketConfig $marketConfig,
        MoneyWrapperInterface $moneyWrapper,
        RestRpcInterface $rpc,
        HideFeaturesConfig $hideFeaturesConfig,
        CryptoManagerInterface $cryptoManager,
        ExternalServiceIdsMapperInterface $cryptoIdsMapper,
        LoggerInterface $logger
    ) {
        $this->marketConfig = $marketConfig;
        $this->moneyWrapper = $moneyWrapper;
        $this->rpc = $rpc;
        $this->hideFeaturesConfig = $hideFeaturesConfig;
        $this->cryptoIdsMapper = $cryptoIdsMapper;
        $this->logger = $logger;

        $this->enabledCryptosIds = array_reduce(
            $cryptoManager->findAllAssets(),
            function ($acc, Crypto $crypto) {
                $acc[$crypto->getSymbol()] = $this->cryptoIdsMapper->getCryptoId($crypto->getMoneySymbol());

                return $acc;
            },
            [],
        );
    }

    public function getCost(string $marketSymbol): array
    {
        $idsToFetch = implode(',', $this->enabledCryptosIds);

        try {
            $response = $this->rpc->send(
                "simple/price?ids={$idsToFetch}&vs_currencies=usd",
                Request::METHOD_GET
            );
            $response = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            /** @var mixed $response */
            $this->logger->error('Invalid response from API', ['exception' => $e, 'response' => $response ?? '']);

            throw new FetchException('Invalid response from API');
        }

        $rates = [];

        foreach (array_keys($this->enabledCryptosIds) as $symbol) {
            if (!isset($response[$this->enabledCryptosIds[$symbol]]['usd'])) {
                throw new FetchException();
            }

            $rates[$symbol] = $this->rate($marketSymbol, $symbol, $response);
        }

        return $rates;
    }

    public function getCosts(): array
    {
        $idsToFetch = implode(',', $this->enabledCryptosIds);

        try {
            $response = $this->rpc->send(
                "simple/price?ids={$idsToFetch}&vs_currencies=usd",
                Request::METHOD_GET
            );
            $response = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            /** @var mixed $response */
            $this->logger->error('Invalid response from API', ['exception' => $e, 'response' => $response ?? '']);

            throw new FetchException('Invalid response from API');
        }

        foreach ($this->enabledCryptosIds as $cryptoId) {
            if (!isset($response[$cryptoId]['usd'])) {
                throw new FetchException();
            }
        }

        $costs = [];
        $usdCosts = $this->marketConfig->getAllMarketCosts();

        foreach (array_keys($usdCosts) as $marketSymbol) {
            if (!$this->hideFeaturesConfig->isCryptoEnabled($marketSymbol) ||
                !array_key_exists($marketSymbol, $this->enabledCryptosIds)
            ) {
                continue;
            }

            foreach (array_keys($this->enabledCryptosIds) as $symbol) {
                $costs[$marketSymbol][$symbol] = $this->rate($marketSymbol, $symbol, $response);
            }
        }

        return $costs;
    }

    protected function rate(string $marketSymbol, string $symbol, array $response): Money
    {
        return $this->moneyWrapper->convert(
            $this->moneyWrapper->parse((string)$this->marketConfig->getTokenMarketCost($marketSymbol), Symbols::USD),
            new Currency($symbol),
            new FixedExchange([
                Symbols::USD => [ $symbol => 1 / $response[$this->enabledCryptosIds[$symbol]]['usd'] ],
            ])
        );
    }
}
