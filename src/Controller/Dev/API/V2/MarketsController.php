<?php declare(strict_types = 1);

namespace App\Controller\Dev\API\V2;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Rest\Route(path="/dev/api/v2/auth/markets")
 * @Cache(smaxage=15, mustRevalidate=true)
 */
class MarketsController extends AbstractFOSRestController
{
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
     * @param ParamFetcherInterface $request
     * @return Response
     */
    public function getMarkets(ParamFetcherInterface $request): Response
    {
        return $this->forward(
            'App\Controller\Dev\API\V1\MarketsController::getMarkets',
            ['request' => $request,],
            [ 'offset' => (int)$request->get('offset'), 'limit' => (int)$request->get('limit'), ]
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
    public function getMarket(string $base, string $quote, ParamFetcherInterface $request): Response
    {
        return $this->forward(
            'App\Controller\Dev\API\V1\MarketsController::getMarket',
            ['base' => $base, 'quote' => $quote, 'request' => $request]
        );
    }
}
