<?php

namespace App\Controller;

use App\Entity\Crypto;
use App\Exchange\Market;
use App\Manager\CryptoManagerInterface;
use App\Manager\ProfileManagerInterface;
use App\Manager\TokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class WalletController extends AbstractController
{
    /**
     * @Route("/wallet", name="wallet")
     * @param ProfileManagerInterface $profileManager
     * @return Response
     */
    public function wallet(
        ProfileManagerInterface $profileManager,
        CryptoManagerInterface $cryptoManager,
        TokenManagerInterface $tokenManager
    ): Response {
        $profile = $this->getUser();
        $user = $profileManager->findProfileByHash($profile->getHash()) ?? $profileManager->createHash($profile);

        $symbols = $cryptoManager->findAllSymbols();
        $markets = array_map(function (Crypto $crypto, $tokenManager) {
            $token = $tokenManager->getOwnToken();
            return null != $token
                ? (new Market($crypto, $token))->getHiddenName()
                : null;
        }, $cryptoManager->findBySymbols($symbols), [$tokenManager]);

        return $this->render('pages/wallet.html.twig', [
            'markets' => $markets,
            'hash' => $user->getHash(),
            'user_id' => $user->getId(),
        ]);
    }
}
