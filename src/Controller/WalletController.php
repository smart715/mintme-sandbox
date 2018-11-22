<?php

namespace App\Controller;

use App\Entity\Crypto;
use App\Entity\Token\Token;
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
        CryptoManagerInterface $cryptoManager,
        TokenManagerInterface $tokenManager,
        BalanceHandler $balanceHandler,
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

        $ownToken = $tokenManager->getOwnToken();
        $markets = $ownToken ? $this->createMarkets($ownToken, $cryptoManager->findAll()) : [];

        return $this->render('pages/wallet.html.twig', [
            'markets' => $markets,
            'hash' => $this->getUser()->getHash(),
            'tokens' => $normalizer->normalize($tokens),
            'predefinedTokens' => $normalizer->normalize($predefinedTokens),
        ]);
    }

    /**
     * @param Crypto[] $cryptos
     * @return Market[]
     */
    private function createMarkets(Token $token, array $cryptos): array
    {
        return array_map(function (Crypto $crypto) use ($token) {
            return null !== $token
                ? (new Market($crypto, $token))->getHiddenName()
                : null;
        }, $cryptos);
    }
}
