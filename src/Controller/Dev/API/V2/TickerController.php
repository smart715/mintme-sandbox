<?php declare(strict_types = 1);

namespace App\Controller\Dev\API\V2;

use App\Config\HideFeaturesConfig;
use App\Entity\Crypto;
use App\Entity\MarketStatus;
use App\Entity\Token\Token;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market\MarketHandlerInterface;
use App\Manager\MarketStatusManagerInterface;
use App\Utils\BaseQuote;
use App\Utils\Converter\RebrandingConverterInterface;
use App\Utils\Symbols;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Nelmio\ApiDocBundle\Annotation\Security;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Rest\Route("/dev/api/v2/open/ticker")
 */
class TickerController extends AbstractFOSRestController
{
    private MarketStatusManagerInterface $marketStatusManager;
    private MarketHandlerInterface $marketHandler;
    private MarketFactoryInterface $marketFactory;
    private RebrandingConverterInterface $rebrandingConverter;
    private HideFeaturesConfig $hideFeaturesConfig;

    public function __construct(
        MarketStatusManagerInterface $marketStatusManager,
        MarketHandlerInterface $marketHandler,
        MarketFactoryInterface $marketFactory,
        RebrandingConverterInterface $rebrandingConverter,
        HideFeaturesConfig $hideFeaturesConfig
    ) {
        $this->marketStatusManager = $marketStatusManager;
        $this->marketHandler = $marketHandler;
        $this->marketFactory = $marketFactory;
        $this->rebrandingConverter = $rebrandingConverter;
        $this->hideFeaturesConfig = $hideFeaturesConfig;
    }

    /**
     * List tickers
     *
     * @Rest\Get("/")
     * @Rest\View(serializerGroups={"dev"})
     * @Rest\QueryParam(
     *     name="offset",
     *     strict=true,
     *     nullable=true,
     *     requirements=@Assert\Range(min="0"),
     *     default=0
     * )
     * @Rest\QueryParam(
     *     name="limit",
     *     strict=true,
     *     nullable=true,
     *     requirements=@Assert\Range(min="1", max="101"),
     *     default=101
     * )
     * @SWG\Parameter(name="offset", in="query", type="integer", description="Results offset [>=0]")
     * @SWG\Parameter(name="limit", in="query", type="integer", description="Results limit [1-101]")
     * @SWG\Response(
     *     response="200",
     *     description="Returns 24-hour pricing and volume summary for each market pair with crypto and deployed tokens available on the exchange."
     * )
     * @SWG\Response(response="400",description="Bad request")
     * @SWG\Tag(name="Open")
     * @Security(name="")
     */
    public function getTicker(ParamFetcherInterface $request): array
    {
        $offset = (int)$request->get('offset');
        $limit = (int)$request->get('limit');

        $assets = [];
        $marketStatuses = $this->marketStatusManager->getCryptoAndDeployedMarketsInfo($offset, $limit);

        $marketStatuses = array_filter($marketStatuses, function ($marketStatus) {
            $isBaseEnabled = $this->hideFeaturesConfig->isCryptoEnabled($marketStatus->getCrypto()->getSymbol());
            $quote = $marketStatus->getQuote();

            if ($quote instanceof Crypto) {
                return $isBaseEnabled && $this->hideFeaturesConfig->isCryptoEnabled($quote->getSymbol());
            }

            return $isBaseEnabled;
        });

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
