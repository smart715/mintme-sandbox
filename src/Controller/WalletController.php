<?php

namespace App\Controller;

use App\Exchange\Balance\BalanceHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class WalletController extends AbstractController
{
    /**
     * @Route("/wallet", name="wallet")
     */
    public function wallet(BalanceHandler $balanceHandler, NormalizerInterface $normalizer): Response
    {
        $tokens = $balanceHandler->balances(
            $this->getUser(),
            $this->getUser()->getRelatedTokens()
        );

        return $this->render('pages/wallet.html.twig', [
            'tokens' => $normalizer->normalize($tokens),
        ]);
    }
}
