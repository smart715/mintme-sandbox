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
     *     description="Returns detailed summary for each crypto or deployed token available on the exchange."
     * )
     * @SWG\Response(response="400",description="Bad request")
     * @SWG\Tag(name="Open")
     * @Security(name="")
     */
    public function getAssets(): array
    {
        $assets = [];
        $cryptos = $this->cryptoManager->findAllIndexed('symbol', true);
        $tokens = $this->tokenManager->getDeployedTokens();
        $tokenMinWithdraw = number_format((float)('1e-' . Token::TOKEN_SUBUNIT), Token::TOKEN_SUBUNIT);
        $makerFee = $this->getParameter('maker_fee_rate');
        $takerFee = $this->getParameter('taker_fee_rate');

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

        foreach ($tokens as $token) {
            if ($token->isBlocked() || !$token->isDeployed()) {
                continue;
            }

            $assets[$this->rebrandingConverter->convert($token->getSymbol())] = [
                'name' => strtolower($this->rebrandingConverter->convert($token->getName())),
                'type_of_token' => $token->getCrypto()
                    ? strtolower($token->getCrypto()->getSymbol())
                    : 'mintme',
                'can_withdraw' => true,
                'can_deposit' => true,
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
