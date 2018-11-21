<?php

namespace App\Controller;

use App\Exchange\Balance\BalanceHandler;
use App\Manager\TokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/** @Route("/wallet") */
class WalletController extends AbstractController
{
    /**
     * @Route(name="wallet")
     */
    public function wallet(
        BalanceHandler $balanceHandler,
        TokenManagerInterface $tokenManager,
        NormalizerInterface $normalizer
    ): Response {
        $tokens = $balanceHandler->balances(
            $this->getUser(),
            $this->getUser()->getRelatedTokens()
        );

        $predefinedTokens = $balanceHandler->balances(
            $this->getUser(),
            $tokenManager->findAllPredefined()
        );

        return $this->render('pages/wallet.html.twig', [
            'tokens' => $normalizer->normalize($tokens),
            'predefinedTokens' => $normalizer->normalize($predefinedTokens),
        ]);
    }
}
