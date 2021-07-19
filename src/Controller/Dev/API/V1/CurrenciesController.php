<?php declare(strict_types = 1);

namespace App\Controller\Dev\API\V1;

use App\Entity\Token\Token;
use App\Exception\ApiNotFoundException;
use App\Manager\TokenManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Rest\Route(path="/dev/api/v1/currencies")
 * @Cache(smaxage=15, mustRevalidate=true)
 */
class CurrenciesController extends DevApiController
{
    /** @var TokenManagerInterface */
    private $tokenManager;

    public function __construct(TokenManagerInterface $tokenManager)
    {
        $this->tokenManager = $tokenManager;
    }

    /**
     * List currencies
     *
     * @Rest\View(serializerGroups={"dev"})
     * @Rest\Get()
     * @SWG\Response(
     *     response="200",
     *     description="Returns deployed mintme currencies and eth tokens list",
     *     @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/Сurrency"))
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
     * @SWG\Tag(name="Currencies")
     */
    public function getCurrencies(ParamFetcherInterface $request): array
    {
        $deployres=array();
        $result=$this->tokenManager->getDeployedTokens(
        (int)$request->get('offset'),
        (int)$request->get('limit')
    );

        foreach ($result as $k => $subobj) {
                if ($subobj->isDeployed())
                    array_push($deployres, $subobj);
            }

         return $deployres;
    }

    /**
     * Get currency info
     *
     * @Rest\View(serializerGroups={"dev"})
     * @Rest\Get("/{name}")
     * @SWG\Response(
     *     response="200",
     *     description="Returns tokens info",
     *     @SWG\Schema(ref="#/definitions/Сurrency")
     * )
     * @SWG\Response(response="404",description="Currency not found")
     * @SWG\Response(response="400",description="Bad request")
     * @SWG\Parameter(name="name", in="path", description="Currency name", type="string")
     * @SWG\Tag(name="Currencies")
     * @throws ApiNotFoundException
     */
    public function getCurrency(string $name): Token
    {
        $this->checkForDisallowedValues($name);
        $token = $this->tokenManager->findByName($name);

        if (!$token) {
            throw new ApiNotFoundException("Currency not found");
        }

        return $token;
    }
}
