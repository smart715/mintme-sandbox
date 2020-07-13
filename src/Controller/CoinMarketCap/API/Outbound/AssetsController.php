<?php declare(strict_types = 1);

namespace App\Controller\CoinMarketCap\API\Outbound;

use App\Manager\CryptoManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;

/**
 * @Rest\Route("/cmc/api/v1/assets")
 */
class AssetsController extends AbstractFOSRestController
{
    /** @var CryptoManagerInterface */
    private $cryptoManager;

    public function __construct(
        CryptoManagerInterface $cryptoManager
    )
    {
        $this->cryptoManager = $cryptoManager;
    }

    /**
     * Get detailed summary for each currency available on the exchange.
     *
     * @Rest\Get("/")
     * @Rest\View()
     */
    public function getAssets(): array
    {
        $data = [];
        $cryptos = $this->cryptoManager->findAllIndexed('symbol', true);
        $makerFee = $this->getParameter('maker_fee_rate');
        $takerFee = $this->getParameter('taker_fee_rate');

        foreach ($cryptos as $crypto) {
            $subUnit = $crypto['showSubunit'];
            $minWithdraw = '1e-' . $subUnit;

            $data[$crypto['symbol']] = [
                'name' => strtolower($crypto['name']),
                'unified_cryptoasset_id' => 1,
                'can_withdraw' => true,
                'can_deposit' => true,
                'min_withdraw' => number_format((float)$minWithdraw, $subUnit),
//                 'max_withdraw' => 0,
                'maker_fee' => $makerFee,
                'taker_fee' => $takerFee,
            ];
        }

        return $data;
    }
}
