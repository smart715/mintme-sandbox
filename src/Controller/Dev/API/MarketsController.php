<?php declare(strict_types = 1);

namespace App\Controller\Dev\API;

use App\Exchange\Market;
use App\Exchange\Market\MarketHandlerInterface;
use App\Exchange\MarketInfo;
use App\Manager\CryptoManagerInterface;
use App\Manager\MarketStatusManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Utils\Converter\RebrandingConverterInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Rest\Route(path="/dev/api/v1/markets")
 * @Security(expression="is_granted('prelaunch')")
 * @Cache(smaxage=15, mustRevalidate=true)
 */
class MarketsController extends AbstractFOSRestController
{
    /** @var MarketStatusManagerInterface */
    private $marketManager;

    /** @var MarketHandlerInterface */
    private $marketHandler;

    /** @var RebrandingConverterInterface */
    private $rebrandingConverter;

    /** @var CryptoManagerInterface */
    private $cryptoManager;

    /** @var TokenManagerInterface */
    private $tokenManager;

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
     * List markets with a day volume information
     *
     * @Rest\View(serializerGroups={"dev"})
     * @Rest\Get()
     * @SWG\Response(
     *     response="200",
     *     description="Returns markets info",
     *     @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/MarketStatus"))
     * )
     * @SWG\Response(response="400",description="Bad request")
     * @Rest\QueryParam(name="offset", requirements="\d+", default="0")
     * @Rest\QueryParam(name="limit", requirements="\d+", default="100")
     * @SWG\Parameter(name="offset", in="query", type="integer", description="Results offset [>0]")
     * @SWG\Parameter(name="limit", in="query", type="integer", description="Results limit [1-500]")
     * @SWG\Tag(name="Markets")
     */
    public function getMarkets(ParamFetcherInterface $fetcher): array
    {
        return array_map(function ($market) {
            return $this->rebrandingConverter->convertMarketStatus($market);
        }, array_values(
            $this->marketManager->getMarketsInfo(
                (int)$fetcher->get('offset'),
                (int)$fetcher->get('limit')
            )
        ));
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
    public function getMarket(string $base, string $quote, ParamFetcherInterface $fetcher): MarketInfo
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

        if (is_null($base) || is_null($quote)) {
            throw new \Exception('Market not found', Response::HTTP_NOT_FOUND);
        }

        return $this->rebrandingConverter->convertMarketInfo($this->marketHandler->getMarketInfo(
            new Market($base, $quote),
            $periods[$fetcher->get('interval')]
        ));
    }

    private function checkForDisallowedValues(string $base, string $quote): void
    {
        $disallowedValues = ['web'];

        if (in_array(mb_strtolower($base), $disallowedValues) || in_array(mb_strtolower($quote), $disallowedValues)) {
            throw new \Exception('Market not found', Response::HTTP_NOT_FOUND);
        }
    }
}
