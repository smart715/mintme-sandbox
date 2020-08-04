<?php declare(strict_types = 1);

namespace App\Controller\Dev\API\V2;

use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Utils\Converter\RebrandingConverterInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;

/**
 * @Rest\Route("/dev/api/v2/public/assets")
 */
class AssetsController extends AbstractFOSRestController
{
    /** @var CryptoManagerInterface */
    private $cryptoManager;

    /** @var TokenManagerInterface */
    private $tokenManager;

    /** @var RebrandingConverterInterface */
    private $rebrandingConverter;

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
     * Get detailed summary for each currency available on the exchange.
     *
     * @Rest\Get("/")
     * @Rest\View()
     */
    public function getAssets(): array
    {
        $assets = [];
        $cryptos = $this->cryptoManager->findAllIndexed('symbol', true);
        $tokens = $this->tokenManager->findAll();
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
                'max_withdraw' => false,
                'maker_fee' => $makerFee,
                'taker_fee' => $takerFee,
            ];
        }

        foreach ($tokens as $token) {
            $deployed = $token->isDeployed();

            $assets[$this->rebrandingConverter->convert($token->getSymbol())] = [
                'name' => strtolower($this->rebrandingConverter->convert($token->getName())),
                'can_withdraw' => $deployed,
                'can_deposit' => $deployed,
                'min_withdraw' => false,
                'max_withdraw' => $this->getParameter('token_quantity'),
                'maker_fee' => $makerFee,
                'taker_fee' => $takerFee,
            ];
        }

        return $assets;
    }
}
