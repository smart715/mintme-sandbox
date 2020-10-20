<?php declare(strict_types = 1);

namespace App\Controller\Dev\API\V2;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Exception\ApiNotFoundException;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market\MarketHandlerInterface;
use App\Manager\MarketStatusManagerInterface;
use App\Utils\BaseQuote;
use App\Utils\Converter\RebrandingConverterInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\Security;
use Swagger\Annotations as SWG;

/**
 * @Rest\Route("/dev/api/v2/open/ticker")
 */
class TickerController extends AbstractFOSRestController
{
    private MarketStatusManagerInterface $marketStatusManager;
    private MarketHandlerInterface $marketHandler;
    private MarketFactoryInterface $marketFactory;
    private RebrandingConverterInterface $rebrandingConverter;

    public function __construct(
        MarketStatusManagerInterface $marketStatusManager,
        MarketHandlerInterface $marketHandler,
        MarketFactoryInterface $marketFactory,
        RebrandingConverterInterface $rebrandingConverter
    ) {
        $this->marketStatusManager = $marketStatusManager;
        $this->marketHandler = $marketHandler;
        $this->marketFactory = $marketFactory;
        $this->rebrandingConverter = $rebrandingConverter;
    }

    /**
     * List tickers
     *
     * @Rest\Get("/")
     * @Rest\View(serializerGroups={"dev"})
     * @SWG\Response(
     *     response="200",
     *     description="Returns 24-hour pricing and volume summary for each market pair available on the exchange."
     * )
     * @SWG\Response(response="400",description="Bad request")
     * @SWG\Tag(name="Open")
     * @Security(name="")
     */
    public function getTicker(): array
    {
        $assets = [];
        $marketStatuses = $this->marketStatusManager->getAllMarketsInfo();

        return array_map(
            function ($marketStatus) use ($assets) {
                $market = $this->marketFactory->create($marketStatus->getCrypto(), $marketStatus->getQuote());

                $marketStatusToday = $this->marketHandler->getMarketStatus($market);

                $market = BaseQuote::reverseMarket($market);

                $base = $market->getBase();
                $quote = $market->getQuote();

                $rebrandedBaseSymbol = $this->rebrandingConverter->convert($base->getSymbol());
                $rebrandedQuoteSymbol = $this->rebrandingConverter->convert($quote->getSymbol());

                $isFrozen =
                    ($base instanceof Crypto && !$base->isExchangeble()) ||
                    ($quote instanceof Crypto && !$quote->isTradable()) ||
                    ($base instanceof Token && $base->isBlocked()) ||
                    ($quote instanceof Token && $quote->isBlocked()) ?
                    1 : 0;

                $assets[$rebrandedBaseSymbol . '_' . $rebrandedQuoteSymbol] = [
                    'base_id' => $market->getBase()->getId(),
                    'quote_id' => $market->getQuote()->getId(),
                    'last_price' => $marketStatusToday['last'],
                    'quote_volume' => $marketStatusToday['volume'],
                    'base_volume' => $marketStatusToday['deal'],
                    'isFrozen' => $isFrozen,
                ];

                return $assets;
            },
            array_values($marketStatuses)
        );
    }
}
