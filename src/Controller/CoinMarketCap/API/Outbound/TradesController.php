<?php declare(strict_types = 1);

namespace App\Controller\CoinMarketCap\API\Outbound;

use App\Controller\Traits\BaseQuoteOrderTrait;
use App\Exception\ApiNotFoundException;
use App\Exchange\Market\MarketFinderInterface;
use App\Exchange\Market\MarketHandlerInterface;
use App\Exchange\Order;
use App\Exchange\Trade\TraderInterface;
use App\Utils\Converter\RebrandingConverterInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;

/**
 * @Rest\Route("/cmc/api/v1/trades")
 */
class TradesController extends AbstractFOSRestController
{

    use BaseQuoteOrderTrait;

    /** @var MarketHandlerInterface */
    private $marketHandler;

    /** @var MarketFinderInterface */
    private $marketFinder;

    /** @var RebrandingConverterInterface */
    private $rebrandingConverter;

    /** @var MoneyWrapperInterface */
    private $moneyWrapper;

    public function __construct(
        MarketFinderInterface $marketFinder,
        RebrandingConverterInterface $rebrandingConverter,
        MarketHandlerInterface $marketHandler,
        MoneyWrapperInterface $moneyWrapper
    ) {
        $this->marketFinder = $marketFinder;
        $this->rebrandingConverter = $rebrandingConverter;
        $this->marketHandler = $marketHandler;
        $this->moneyWrapper = $moneyWrapper;
    }

    /**
     * Get data on all recently completed trades for a given market pair.
     *
     * @Rest\Get("/{market_pair}")
     * @Rest\View()
     */
    public function getTrades(string $market_pair): array
    {
        $marketPair = explode('_', $market_pair);

        $base = $marketPair[0] ?? '';
        $quote = $marketPair[1] ?? '';

        $base = $this->rebrandingConverter->reverseConvert($base);
        $quote = $this->rebrandingConverter->reverseConvert($quote);

        $market = $this->marketFinder->find($base, $quote);

        if (!$market) {
            throw new ApiNotFoundException('Market pair not found');
        }

        $this->fixBaseQuoteOrder($market);

        return array_map(function ($order) {
            $order = $this->rebrandingConverter->convertOrder($order);

            return [
                'trade_id' => $order->getId(),
                'price' => $order->getPrice(),
                'base_volume' => $order->getAmount(),
                'quote_volume' => $order->getAmount()->multiply($this->moneyWrapper->format($order->getPrice())),
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
