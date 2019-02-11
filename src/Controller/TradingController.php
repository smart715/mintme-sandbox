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
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @Security(expression="is_granted('prelaunch')")
 */
class TradingController extends AbstractController
{
    /**
     * @Route("/trading", name="trading")
     */
    public function trading(MarketManagerInterface $marketManager): Response
    {
        return $this->render('pages/trading.html.twig', [
            'markets' => $marketManager->getAllMarkets(),
        ]);
    }
}
