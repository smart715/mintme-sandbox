<?php declare(strict_types = 1);

namespace App\Controller\Dev\API\V2;

use App\Controller\Dev\API\V1\DevApiController;
use App\Exception\ApiNotFoundException;
use App\Exchange\Market\MarketFinderInterface;
use App\Exchange\Market\MarketHandlerInterface;
use App\Exchange\MarketInfo;
use App\Manager\MarketStatusManagerInterface;
use App\Utils\Converter\RebrandingConverterInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Rest\Route(path="/dev/api/v2/auth/markets")
 * @Cache(smaxage=15, mustRevalidate=true)
 */
class MarketsController extends DevApiController
{
    private MarketStatusManagerInterface $marketManager;
    private MarketHandlerInterface $marketHandler;
    private RebrandingConverterInterface $rebrandingConverter;
    private MarketFinderInterface $marketFinder;

    public function __construct(
        MarketStatusManagerInterface $marketManager,
        MarketHandlerInterface $marketHandler,
        RebrandingConverterInterface $rebrandingConverter,
        MarketFinderInterface $marketFinder
    ) {
        $this->marketManager = $marketManager;
        $this->marketHandler = $marketHandler;
        $this->rebrandingConverter = $rebrandingConverter;
        $this->marketFinder = $marketFinder;
    }

    /**
     * List crypto or deployed token markets with a day volume information
     *
     * @Rest\View(serializerGroups={"dev", "APIv2"})
     * @Rest\Get()
     * @SWG\Response(
     *     response="200",
     *     description="Returns markets info",
     *     @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/MarketStatus"))
     * )
     * @SWG\Response(response="400",description="Bad request")
     * @Rest\QueryParam(
     *     name="offset",
     *     requirements=@Assert\Range(min="0"),
     *     nullable=false,
     *     allowBlank=false,
     *     strict=true
     * )
     * @Rest\QueryParam(
     *     name="limit",
     *     requirements=@Assert\Range(min="1", max="500"),
     *     nullable=false,
     *     allowBlank=false,
     *     strict=true
     * )
     * @SWG\Parameter(name="offset", in="query", type="integer", description="Results offset [>=0]")
     * @SWG\Parameter(name="limit", in="query", type="integer", description="Results limit [1-500]")
     * @SWG\Tag(name="Markets")
     */
    public function getMarkets(ParamFetcherInterface $request): array
    {
        $offset = (int)$request->get('offset');
        $limit = (int)$request->get('limit');

        return array_slice(
            array_map(function ($market) {
                return $this->rebrandingConverter->convertMarketStatus($market);
            }, array_values(
                $this->marketManager->getMarketsInfo(
                    $offset,
                    $limit,
                    'monthVolume',
                    'DESC',
                    2,
                    null
                )
            )),
            0,
            $limit
        );
    }

    /**
     * Get market info
     *
     * @Rest\View(serializerGroups={"dev"})
     * @Rest\Get("/{base}/{quote}")
     * @SWG\Response(
     *     response="200",
     *     description="Returns markets info",
     *     @SWG\Schema(ref="#/definitions/MarketStatusDetails")
     * )
     * @SWG\Response(response="404",description="Market not found")
     * @SWG\Response(response="400",description="Bad request")
     * @Rest\QueryParam(name="interval", requirements="(1h|1d|7d)", default="1d")
     * @SWG\Parameter(
     *     name="interval",
     *     type="string",
     *     in="query",
     *     enum={"1h","1d","7d"},
     *     description="Interval to be shown"
     * )
     * @SWG\Parameter(name="base", in="path", description="Base name", type="string")
     * @SWG\Parameter(name="quote", in="path", description="Quote name", type="string")
     * @SWG\Tag(name="Markets")
     */
    public function getMarket(string $base, string $quote, ParamFetcherInterface $request): MarketInfo
    {
        $this->checkForDisallowedValues($base, $quote);

        $periods = [
            '1h'   => 3600,
            '1d'   => 86400,
            '7d'   => 604800,
        ];

        $convertedBase = $this->rebrandingConverter->reverseConvert(mb_strtolower($base));
        $convertedQuote = $this->rebrandingConverter->reverseConvert(mb_strtolower($quote));

        $market = $this->marketFinder->find($convertedQuote, $convertedBase);

        if (!$market) {
            throw new ApiNotFoundException('Market not found');
        }

        $marketInfo = $this->rebrandingConverter->convertMarketInfo($this->marketHandler->getMarketInfo(
            $market,
            $periods[$request->get('interval')]
        ));

        $marketInfo->setTokenName($quote);
        $marketInfo->setCryptoSymbol($base);

        return $marketInfo;
    }
}
