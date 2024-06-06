<?php declare(strict_types = 1);

namespace App\Controller\Dev\API\V2;

use App\Exception\ApiNotFoundException;
use App\Exchange\Market\MarketFinderInterface;
use App\Exchange\Market\MarketHandlerInterface;
use App\Exchange\Order;
use App\Manager\MarketStatusManagerInterface;
use App\Utils\BaseQuote;
use App\Utils\Converter\RebrandingConverterInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\Security;
use Swagger\Annotations as SWG;

/**
 * @Rest\Route("/dev/api/v2/open/trades")
 */
class TradesController extends AbstractFOSRestController
{
    private MarketHandlerInterface $marketHandler;
    private MarketFinderInterface $marketFinder;
    private RebrandingConverterInterface $rebrandingConverter;
    private MoneyWrapperInterface $moneyWrapper;
    private MarketStatusManagerInterface $marketStatusManager;

    public function __construct(
        MarketFinderInterface $marketFinder,
        RebrandingConverterInterface $rebrandingConverter,
        MarketHandlerInterface $marketHandler,
        MoneyWrapperInterface $moneyWrapper,
        MarketStatusManagerInterface $marketStatusManager
    ) {
        $this->marketFinder = $marketFinder;
        $this->rebrandingConverter = $rebrandingConverter;
        $this->marketHandler = $marketHandler;
        $this->moneyWrapper = $moneyWrapper;
        $this->marketStatusManager = $marketStatusManager;
    }

    /**
     * Get completed trades
     *
     * @Rest\Get("/{market_pair}")
     * @Rest\View(serializerGroups={"dev"})
     * @SWG\Response(
     *     response="200",
     *     description="Returns all recently completed trades for a given market pair."
     * )
     * @SWG\Response(response="400",description="Bad request")
     * @SWG\Tag(name="Open")
     * @Security(name="")
     */
    public function getTrades(string $market_pair): array
    {
        $marketPair = explode('_', $market_pair);

        $base = $marketPair[0] ?? '';
        $quote = $marketPair[1] ?? '';

        $base = $this->rebrandingConverter->reverseConvert($base);
        $quote = $this->rebrandingConverter->reverseConvert($quote);

        $market = $this->marketFinder->find($base, $quote, true);

        if (!$market || !$this->marketStatusManager->isValid($market)) {
            throw new ApiNotFoundException('Market pair not found');
        }

        $market = BaseQuote::reverseMarket($market);

        return array_map(function ($order) {
            $rebrandedOrder = $this->rebrandingConverter->convertOrder($order);

            return [
                'trade_id' => $rebrandedOrder->getId(),
                'price' => $rebrandedOrder->getPrice(),
                'base_volume' => $rebrandedOrder->getAmount(),
                'quote_volume' => $rebrandedOrder->getAmount()->multiply($this->moneyWrapper->format($rebrandedOrder->getPrice())),
                'timestamp' => $rebrandedOrder->getTimestamp(),
                'type' => array_search($rebrandedOrder->getSide(), Order::SIDE_MAP),
            ];
        }, $this->marketHandler->getExecutedOrders(
            $market
        ));
    }
}
