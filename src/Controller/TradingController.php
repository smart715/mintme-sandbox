<?php

namespace App\Controller;

use App\Exchange\Market;
use App\Manager\CryptoManagerInterface;
use App\Manager\MarketManagerInterface;
use App\Manager\TokenManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Security(expression="is_granted('prelaunch')")
 */
class TradingController extends AbstractController
{
    /**
     * @Route("/trading", name="trading")
     */
    public function trading(
        MarketManagerInterface $marketManager
    ): Response {
        $allMarkets = $marketManager->getAllMarkets();
        $marketNames = array_column(
            array_map(function (Market $market) {
                return  [
                    'hiddenName' => $market->getHiddenName(),
                    'currencies' => [ $market->getTokenName(), $market->getCurrencySymbol() ],
                ];
            }, $allMarkets),
            'currencies',
            'hiddenName'
        );
        return $this->render('pages/trading.html.twig', [
            'marketNames' => $marketNames,
        ]);
    }
}
