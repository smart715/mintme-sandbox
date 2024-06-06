<?php declare(strict_types = 1);

namespace App\Controller\Dev\API\V1;

use App\Entity\Token\Token;
use App\Exception\ApiNotFoundException;
use App\Exception\NotDeployedTokenException;
use App\Exchange\Market;
use App\Exchange\Market\MarketHandlerInterface;
use App\Exchange\MarketInfo;
use App\Manager\CryptoManagerInterface;
use App\Manager\MarketStatusManager;
use App\Manager\MarketStatusManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Utils\Converter\RebrandingConverterInterface;
use App\Utils\Symbols;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Rest\Route(path="/dev/api/v1/markets")
 * @Cache(smaxage=15, mustRevalidate=true)
 */
class MarketsController extends DevApiController
{
    private MarketStatusManagerInterface $marketManager;
    private MarketHandlerInterface $marketHandler;
    private RebrandingConverterInterface $rebrandingConverter;
    private CryptoManagerInterface $cryptoManager;
    private TokenManagerInterface $tokenManager;

    public function __construct(
        MarketStatusManagerInterface $marketManager,
        MarketHandlerInterface $marketHandler,
        RebrandingConverterInterface $rebrandingConverter,
        CryptoManagerInterface $cryptoManager,
        TokenManagerInterface $tokenManager
    ) {
        $this->marketManager = $marketManager;
        $this->marketHandler = $marketHandler;
        $this->rebrandingConverter = $rebrandingConverter;
        $this->cryptoManager = $cryptoManager;
        $this->tokenManager = $tokenManager;
    }

    /**
     * List crypto or deployed token markets with a day volume information
     *
     * @Rest\View(serializerGroups={"dev"})
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

        $predefinedMarkets = $this->marketManager->getPredefinedMarketStatuses();
        $filteredMarkets = $this->marketManager->getFilteredMarketStatuses(
            $offset,
            $limit,
            'monthVolume',
            'DESC',
            [MarketStatusManager::buildDeployedOnlyFilter(Symbols::MINTME)],
            null
        );

        $markets = array_merge(
            $predefinedMarkets,
            $filteredMarkets
        );

        return array_slice(
            array_map(function ($market) {
                return $this->rebrandingConverter->convertMarketStatus($market);
            }, array_values(
                $markets
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

        $base = $this->rebrandingConverter->reverseConvert(mb_strtolower($base));
        $quote = $this->rebrandingConverter->reverseConvert(mb_strtolower($quote));

        $base = $this->cryptoManager->findBySymbol($base);
        $quote = $this->cryptoManager->findBySymbol($quote) ?? $this->tokenManager->findByName($quote);

        if ($quote instanceof Token && !$quote->isDeployed()) {
            throw new NotDeployedTokenException();
        }

        if (is_null($base) || is_null($quote)) {
            throw new ApiNotFoundException('Market not found');
        }

        return $this->rebrandingConverter->convertMarketInfo($this->marketHandler->getMarketInfo(
            new Market($base, $quote),
            $periods[$request->get('interval')]
        ));
    }
}
