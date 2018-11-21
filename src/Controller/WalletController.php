<?php

namespace App\Controller;

use App\Entity\Crypto;
use App\Exchange\Balance\BalanceHandler;
use App\Exchange\Market;
use App\Manager\CryptoManagerInterface;
use App\Manager\ProfileManagerInterface;
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
        ProfileManagerInterface $profileManager,
        CryptoManagerInterface $cryptoManager,
        TokenManagerInterface $tokenManager,
        BalanceHandler $balanceHandler,
        NormalizerInterface $normalizer
    ): Response {
        $tokens = $balanceHandler->balances(
            $this->getUser(),
            $this->getUser()->getRelatedTokens()
        );
        $user = $profileManager->findProfileByHash($this->getUser()->getHash());

        $predefinedTokens = $balanceHandler->balances(
            $this->getUser(),
            $tokenManager->findAllPredefined()
        );

        $symbols = $cryptoManager->findAll();
        $markets = array_map(function (Crypto $crypto, $tokenManager) {
            $token = $tokenManager->getOwnToken();
            return null != $token
                ? (new Market($crypto, $token))->getHiddenName()
                : null;
        }, $cryptoManager->findBySymbols($symbols), [$tokenManager]);
        return $this->render('pages/wallet.html.twig', [
            'markets' => $markets,
            'hash' => $user->getHash(),
            'tokens' => $normalizer->normalize($tokens),
            'predefinedTokens' => $normalizer->normalize($predefinedTokens),
        ]);
    }
}
