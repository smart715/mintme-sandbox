<?php declare(strict_types = 1);

namespace App\Communications;

use App\Communications\Exception\FetchException;
use App\Config\HideFeaturesConfig;
use App\Entity\Crypto;
use App\Manager\CryptoManagerInterface;
use App\Security\Config\DisabledServicesConfig;
use App\Utils\Converter\RebrandingConverterInterface;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Money\Currency;
use Money\Exchange\FixedExchange;
use Money\Money;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

abstract class AbstractCostFetcher
{
    private const CACHE_COST_KEY = 'cache-cost';

    protected RestRpcInterface $rpc;
    protected MoneyWrapperInterface $moneyWrapper;
    protected CacheInterface $cache;
    protected CryptoManagerInterface $cryptoManager;
    protected DisabledServicesConfig $disabledServicesConfig;
    protected ExternalServiceIdsMapperInterface $cryptoIdsMapper;
    protected HideFeaturesConfig $hideFeaturesConfig;
    protected array $cryptoIds;
    /** @var Crypto[] */
    protected array $cryptos;
    protected LoggerInterface $logger;
    protected RebrandingConverterInterface $rebrandingConverter;

    public function __construct(
        RestRpcInterface $rpc,
        MoneyWrapperInterface $moneyWrapper,
        CacheInterface $cache,
        CryptoManagerInterface $cryptoManager,
        DisabledServicesConfig $disabledServicesConfig,
        ExternalServiceIdsMapperInterface $cryptoIdsMapper,
        HideFeaturesConfig $hideFeaturesConfig,
        LoggerInterface $logger,
        RebrandingConverterInterface $rebrandingConverter
    ) {
        $this->rpc = $rpc;
        $this->moneyWrapper = $moneyWrapper;
        $this->cache = $cache;
        $this->cryptoManager = $cryptoManager;
        $this->disabledServicesConfig = $disabledServicesConfig;
        $this->cryptoIdsMapper = $cryptoIdsMapper;
        $this->hideFeaturesConfig = $hideFeaturesConfig;
        $this->logger = $logger;
        $this->rebrandingConverter = $rebrandingConverter;

        $cryptos = [];
        $this->cryptoIds = [];
        $blockchainDeployStatus = $this->disabledServicesConfig->getBlockchainDeployStatus();

        foreach ($this->cryptoManager->findAll() as $crypto) {
            $symbol = $crypto->getSymbol();

            if (!isset($blockchainDeployStatus[$this->rebrandingConverter->convert($symbol)]) ||
                !$blockchainDeployStatus[$this->rebrandingConverter->convert($symbol)]) {
                continue;
            }

            if (($cryptoId = $this->cryptoIdsMapper->getCryptoId($symbol)) && $crypto->isAsset()) {
                $this->cryptoIds[$symbol] = $cryptoId;
            }

            $cryptos[] = $crypto;
        }

        $this->cryptos = $cryptos;
    }

    abstract public function getCosts(): array;

    protected function fetchPrices(): array
    {
        return $this->cache->get(self::CACHE_COST_KEY, function (ItemInterface $item) {
            $item->expiresAfter(1800);

            $ids = implode(',', $this->cryptoIds);

            try {
                $response = $this->rpc->send(
                    "simple/price?ids=${ids}&vs_currencies=usd",
                    Request::METHOD_GET
                );
                $response = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                /** @var mixed $response */
                $this->logger->error('Invalid response from API', ['exception' => $e, 'response' => $response ?? '']);

                throw new FetchException('Invalid response from API');
            }

            foreach ($this->cryptoIds as $cryptoId) {
                if (!isset($response[$cryptoId]['usd'])) {
                    throw new FetchException();
                }
            }

            return $response;
        });
    }

    protected function rate(string $cost, string $symbol, array $prices): Money
    {
        /** @var Crypto $crypto */
        $crypto = $this->cryptoManager->findBySymbol($symbol);

        $converted = $this->moneyWrapper->convert(
            $this->moneyWrapper->parse($cost, Symbols::USD),
            new Currency($symbol),
            new FixedExchange([
                Symbols::USD => [ $symbol =>  1 / $prices[$this->cryptoIds[$symbol]]['usd']],
            ])
        );

        $roundedToSubunit = (string)BigDecimal::of($this->moneyWrapper->format($converted))
            ->multipliedBy('1')
            ->toScale($crypto->getShowSubunit(), RoundingMode::HALF_UP);

        return $this->moneyWrapper->parse($roundedToSubunit, $crypto->getSymbol());
    }
}
