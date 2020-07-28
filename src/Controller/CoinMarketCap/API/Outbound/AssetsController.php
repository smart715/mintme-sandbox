<?php declare(strict_types = 1);

namespace App\Controller\CoinMarketCap\API\Outbound;

use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;

/**
 * @Rest\Route("/cmc/api/v1/assets")
 */
class AssetsController extends AbstractFOSRestController
{
    /** @var CryptoManagerInterface */
    private $cryptoManager;

    /** @var TokenManagerInterface */
    private $tokenManager;

    public function __construct(
        CryptoManagerInterface $cryptoManager,
        TokenManagerInterface $tokenManager
    ) {
        $this->cryptoManager = $cryptoManager;
        $this->tokenManager = $tokenManager;
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

            $assets[$crypto['symbol']] = [
                'name' => strtolower($crypto['name']),
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

            $assets[$token->getSymbol()] = [
                'name' => strtolower($token->getName()),
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
