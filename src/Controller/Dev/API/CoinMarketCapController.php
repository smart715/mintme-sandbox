<?php declare(strict_types = 1);

namespace App\Controller\Dev\API;

use App\Exception\ApiNotFoundException;
use App\Exchange\Market;
use App\Exchange\Market\MarketFetcherInterface;
use App\Exchange\Market\MarketHandlerInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Utils\Converter\MarketNameConverterInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Rest\Route("/api/v1")
 */
class CoinMarketCapController extends AbstractFOSRestController
{
    /** @var TokenManagerInterface */
    private $tokenManager;

    /** @var CryptoManagerInterface */
    private $cryptoManager;

    public function __construct(TokenManagerInterface $tokenManager, CryptoManagerInterface $cryptoManager)
    {
        $this->tokenManager = $tokenManager;
        $this->cryptoManager = $cryptoManager;
    }

    /**
     * Get data for all tickers and all markets.
     *
     * @Rest\Get("/summary")
     * @Rest\View()
     */
    public function getSummary(MarketFetcherInterface $marketFetcher): array
    {
        return $marketFetcher->getMarketList();
    }

    /**
     * Get detailed summary for each currency available on the exchange.
     *
     * @Rest\Get("/assets")
     * @Rest\View()
     */
    public function getAssets(): array
    {
        $data = [];
        /** @var array $cryptos */
        $cryptos = $this->cryptoManager->findAllIndexed('symbol', true);
        $makerFee = $this->getParameter('maker_fee_rate');
        $takerFee = $this->getParameter('taker_fee_rate');

        foreach ($cryptos as $crypto) {
            $subUnit = $crypto['showSubunit'];
            $minWithdraw = '1e-' . $subUnit;

            $data[$crypto['symbol']] = [
                'name' => strtolower($crypto['name']),
                'unified_cryptoasset_id' => 1,
                'can_withdraw' => true,
                'can_deposit' => true,
                'min_withdraw' => number_format((float)$minWithdraw, $subUnit),
//                 'max_withdraw' => 0,
                'maker_fee' => $makerFee,
                'taker_fee' => $takerFee,
            ];
        }

        return $data;
    }

    /**
     * Get 24-hour pricing and volume summary for each market pair available on the exchange.
     *
     * @Rest\Get("/ticker")
     * @Rest\View()
     */
    public function getTicker(): array
    {
        //TODO:

        return [];
    }

    /**
     * Get complete level 2 order book (arranged by best asks/bids) with full depth returned for a given market pair.
     *
     * @Rest\Get("/orderbook/{market_pair}")
     * @Rest\QueryParam(name="depth", default=100)
     * @Rest\QueryParam(name="level", default=3)
     * @Rest\View()
     */
    public function getOrderBook(
        string $market_pair,
        ParamFetcherInterface $request,
        MarketHandlerInterface $marketHandler
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
        $data = $marketHandler->getOrdersDepth($market, $depth);
        $data['timestamp'] = time();

        return $data;
    }

    /**
     * Get data on all recently completed trades for a given market pair.
     *
     * @Rest\Get("/trades/{market_pair}")
     * @Rest\View()
     */
    public function getTrades(
        string $market_pair,
        MarketFetcherInterface $marketFetcher,
        MarketNameConverterInterface $marketNameConverter
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

        return $marketFetcher->getExecutedOrders($marketNameConverter->convert($market));
    }
}
