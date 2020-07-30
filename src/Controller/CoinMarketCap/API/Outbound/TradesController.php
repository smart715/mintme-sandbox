<?php declare(strict_types = 1);

namespace App\Controller\CoinMarketCap\API\Outbound;

use App\Communications\CryptoRatesFetcherInterface;
use App\Controller\Traits\BaseQuoteOrderTrait;
use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Exception\ApiNotFoundException;
use App\Exchange\Market;
use App\Exchange\Market\MarketFetcherInterface;
use App\Exchange\Market\MarketFinderInterface;
use App\Exchange\Market\MarketHandlerInterface;
use App\Exchange\Order;
use App\Exchange\Trade\TraderInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use Money\Currency;
use Money\Exchange\FixedExchange;
use Money\Money;
use App\Utils\Converter\MarketNameConverterInterface;
use App\Utils\Converter\RebrandingConverterInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Safe\DateTimeImmutable;

/**
 * @Rest\Route("/cmc/api/v1/trades")
 */
class TradesController extends AbstractFOSRestController
{

    use BaseQuoteOrderTrait;

    /** @var TraderInterface */
    private $trader;

    /** @var MarketHandlerInterface */
    private $marketHandler;

    /** @var MarketFinderInterface */
    private $marketFinder;

    /** @var RebrandingConverterInterface */
    private $rebrandingConverter;

    /** @var MoneyWrapperInterface */
    private $moneyWrapper;

    /** @var CryptoRatesFetcherInterface */
    private $cryptoRatesFetcher;

    public function __construct(
        TraderInterface $trader,
        MarketFinderInterface $marketFinder,
        RebrandingConverterInterface $rebrandingConverter,
        MarketHandlerInterface $marketHandler,
        MoneyWrapperInterface $moneyWrapper,
        CryptoRatesFetcherInterface $cryptoRatesFetcher
    ) {
        $this->trader = $trader;
        $this->marketFinder = $marketFinder;
        $this->rebrandingConverter = $rebrandingConverter;
        $this->marketHandler = $marketHandler;
        $this->moneyWrapper = $moneyWrapper;
        $this->cryptoRatesFetcher = $cryptoRatesFetcher;
    }

    /**
     * Get data on all recently completed trades for a given market pair.
     *
     * @Rest\Get("/{market_pair}")
     * @Rest\View()
     */
    public function getTrades(string $market_pair)
    {
        $marketPair = explode('_', $market_pair);

        $base = $marketPair[0] ?? '';
        $quote = $marketPair[1] ?? '';

        $base = $this->rebrandingConverter->reverseConvert($base);
        $quote = $this->rebrandingConverter->reverseConvert($quote);

        $market = $this->marketFinder->find($base, $quote);

        $this->fixBaseQuoteOrder($market);

        if (is_null($market)) {
            throw new ApiNotFoundException('Market pair not found');
        }

        $exchange = new FixedExchange($this->cryptoRatesFetcher->fetch());

        return array_map(function ($order) use($market, $exchange) {
            $order = $this->rebrandingConverter->convertOrder($order);
            return $order;
            return [
                'trade_id' => $order->getId(),
                'price' => $order->getPrice(),
                'base_volume' => $order->getAmount(),
                'quote_volume' => $this->moneyWrapper->convert(
                    $market->getBase()->getSymbol(),
                    $order->getMarket()->getQuote(),
                    $exchange
                ),
                'timestamp' => $order->getTimestamp(),
                'type' => array_search($order->getSide(), Order::SIDE_MAP),
            ];
        }, $this->marketHandler->getExecutedOrders(
            $market,
            0,
            100
        ));
    }
}
