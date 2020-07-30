<?php declare(strict_types = 1);

namespace App\Controller\CoinMarketCap\API\Outbound;

use App\Exception\ApiNotFoundException;
use App\Exchange\Market;
use App\Exchange\Trade\TraderInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Rest\Route("/cmc/api/v1/orderbook")
 */
class OrderbookController extends AbstractFOSRestController
{
    /** @var CryptoManagerInterface */
    private $cryptoManager;

    /** @var TokenManagerInterface */
    private $tokenManager;

    /** TraderInterface */
    private $trader;

    public function __construct(
        TokenManagerInterface $tokenManager,
        CryptoManagerInterface $cryptoManager,
        TraderInterface $trader
    ) {
        $this->tokenManager = $tokenManager;
        $this->cryptoManager = $cryptoManager;
        $this->trader = $trader;
    }
    /**
     * Get complete level 2 order book (arranged by best asks/bids) with full depth returned for a given market pair.
     *
     * @Rest\Get("/{market_pair}")
     * @Rest\QueryParam(
     *     name="limit",
     *     default="100",
     *     requirements=@Assert\Range(min="1", max="101"),
     *     nullable=false,
     *     allowBlank=false,
     *     strict=true
     * )
     * @Rest\QueryParam(
     *     name="interval",
     *     default="0",
     *     nullable=false,
     *     allowBlank=false,
     *     strict=true
     * )
     * @Rest\View()
     */
    public function getOrderBook(ParamFetcherInterface $request, string $market_pair): array
    {
        $marketNames = explode('_', $market_pair);
        $base = $marketNames[0] ?? '';
        $quote = $marketNames[1] ?? '';
        $base = $this->tokenManager->findByName($base);
        $quote = $this->cryptoManager->findBySymbol($quote) ?? $this->tokenManager->findByName($quote);

        if (is_null($base) || is_null($quote)) {
            throw new ApiNotFoundException('Market pair not found');
        }

        $market = new Market($quote, $base);

        return $this->trader->getOrderDepth(
            $market,
            [
                'limit' => (int)$request->get('limit'),
                'interval' => (string)$request->get('interval'),
            ]
        );
    }
}
