<?php declare(strict_types = 1);

namespace App\Controller\Dev\API\V2;

use App\Entity\Token\Token;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Utils\Converter\RebrandingConverterInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Nelmio\ApiDocBundle\Annotation\Security;
use Swagger\Annotations as SWG;

/**
 * @Rest\Route("/dev/api/v2/open/assets")
 */
class AssetsController extends AbstractFOSRestController
{
    private CryptoManagerInterface $cryptoManager;
    private TokenManagerInterface $tokenManager;
    private RebrandingConverterInterface $rebrandingConverter;

    public function __construct(
        CryptoManagerInterface $cryptoManager,
        TokenManagerInterface $tokenManager,
        RebrandingConverterInterface $rebrandingConverter
    ) {
        $this->cryptoManager = $cryptoManager;
        $this->tokenManager = $tokenManager;
        $this->rebrandingConverter = $rebrandingConverter;
    }

    /**
     * List assets
     *
     * @Rest\Get("/")
     * @Rest\View(serializerGroups={"dev"})
     * @SWG\Response(
     *     response="200",
     *     description="Returns detailed summary for each currency available on the exchange."
     * )
     * @SWG\Parameter(name="deployed", in="query",default="false", description="List only deployed tokens", type="boolean", required=false)
     * @SWG\Response(response="400",description="Bad request")
     * @SWG\Tag(name="Open")
     * @Rest\QueryParam(name="deployed", nullable=true, allowBlank=true)
     * @Security(name="")
     * @return mixed[]
     */
    public function getAssets(ParamFetcherInterface $paramFetcher): array
    {
        $onlyDeployed = $paramFetcher->get('deployed');
        $assets = [];
        $cryptos = $this->cryptoManager->findAllIndexed('symbol', true);
        $tokens = $this->tokenManager->findAll();
        $tokenMinWithdraw = number_format((float)('1e-' . Token::TOKEN_SUBUNIT), Token::TOKEN_SUBUNIT);
        $makerFee = $this->getParameter('maker_fee_rate');
        $takerFee = $this->getParameter('taker_fee_rate');

        if (!$onlyDeployed) {
            foreach ($cryptos as $crypto) {
                $subUnit = $crypto['showSubunit'];
                $minWithdraw = '1e-' . $subUnit;

                $assets[$this->rebrandingConverter->convert($crypto['symbol'])] = [
                    'name' => strtolower($this->rebrandingConverter->convert($crypto['name'])),
                    'can_withdraw' => true,
                    'can_deposit' => true,
                    'min_withdraw' => number_format((float)$minWithdraw, $subUnit),
                    'maker_fee' => $makerFee,
                    'taker_fee' => $takerFee,
                ];
            }
        }

        foreach ($tokens as $token) {
            $deployed = $token->isDeployed();

            if ($onlyDeployed && !$deployed) {
                continue;
            }

            $assets[$this->rebrandingConverter->convert($token->getSymbol())] = [
                'name' => strtolower($this->rebrandingConverter->convert($token->getName())),
                'can_withdraw' => $deployed,
                'can_deposit' => $deployed,
                'min_withdraw' => $tokenMinWithdraw,
                'max_withdraw' => $this->getParameter('token_quantity'),
                'maker_fee' => $makerFee,
                'taker_fee' => $takerFee,
                'token_address' => $token->getAddress(),
            ];
        }

        return $assets;
    }
}
