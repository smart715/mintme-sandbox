<?php

namespace App\Controller;

use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @Security(expression="is_granted('prelaunch')")
 */
class TradingController extends Controller
{
    /**
     * @Route("/trading", name="trading")
     */
    public function trading(MarketFactoryInterface $marketManager): Response
    {
        return $this->render('pages/trading.html.twig', [
            'markets' => $marketManager->createAll(),
        ]);
    }
}
