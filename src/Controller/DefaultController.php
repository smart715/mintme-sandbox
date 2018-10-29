<?php

namespace App\Controller;

use App\Exchange\Market;
use App\Manager\CryptoManager;
use App\Manager\MarketManager;
use App\Manager\TokenManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function index(): Response
    {
        return $this->render('pages/index.html.twig');
    }

    /**
     * @Route("/trading", name="trading")
     */
    public function trading(
        MarketManager $marketManager,
        TokenManager $tokenManager,
        CryptoManager $cryptoManager
    ): Response {
        $allMarkets = $marketManager->getAllMarkets($cryptoManager, $tokenManager);
        $marketNames = array_column(
            array_map(function (Market $market) {
                return  [
                    'hiddenName' => $market->getHiddenName(),
                    'currencies' => [ $market->getTokenName(), $market->getCurrencySymbol()],
                ];
            }, $allMarkets),
            'currencies',
            'hiddenName'
        );
        return $this->render('pages/trading.html.twig', [
            'marketNames' => $marketNames,
        ]);
    }

    /**
     * @Route("/wallet", name="wallet")
     */
    public function wallet(): Response
    {
        return $this->render('pages/wallet.html.twig');
    }
}
