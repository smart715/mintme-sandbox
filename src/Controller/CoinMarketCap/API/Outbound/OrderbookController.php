<?php declare(strict_types = 1);

namespace App\Controller\CoinMarketCap\API\Outbound;

use App\Exception\ApiNotFoundException;
use App\Exchange\Market;
use App\Exchange\Market\MarketHandlerInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;

/**
 * @Rest\Route("/cmc/api/v1/orderbook")
 */
class OrderbookController extends AbstractFOSRestController
{
    /** @var MarketHandlerInterface */
    private $marketHandler;

    /** @var CryptoManagerInterface */
    private $cryptoManager;

    /** @var TokenManagerInterface */
    private $tokenManager;

    public function __construct(
        MarketHandlerInterface $marketHandler,
        TokenManagerInterface $tokenManager,
        CryptoManagerInterface $cryptoManager
    ) {
        $this->marketHandler = $marketHandler;
        $this->tokenManager = $tokenManager;
        $this->cryptoManager = $cryptoManager;
    }
    /**
     * Get complete level 2 order book (arranged by best asks/bids) with full depth returned for a given market pair.
     *
     * @Rest\Get("/{market_pair}")
     * @Rest\QueryParam(name="depth", default=100)
     * @Rest\QueryParam(name="level", default=3)
     * @Rest\View()
     */
    public function getOrderBook(
        string $market_pair,
        ParamFetcherInterface $request
    ): array {
        $marketNames = explode('_', $market_pair);
        $base = $marketNames[0] ?? '';
        $quote = $marketNames[1] ?? '';
        $base = $this->cryptoManager->findBySymbol($base);
        $quote = $this->cryptoManager->findBySymbol($quote) ?? $this->tokenManager->findByName($quote);

        if (is_null($base) || is_null($quote)) {
            throw new ApiNotFoundException('Market not found');
        }

        $market = new Market($base, $quote);
        $depth = $request->get('depth');
        $data = $this->marketHandler->getOrdersDepth($market, $depth);
        $data['timestamp'] = time();

        return $data;
    }
}
