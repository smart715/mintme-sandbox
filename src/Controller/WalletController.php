<?php

namespace App\Controller;

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
        TokenManagerInterface $tokenManager,
        CryptoManagerInterface $cryptoManager
    ): Response {
        if (!$this->getUser()) {
            return $this->redirectToRoute('fos_user_security_login');
        }

        $user = $profileManager->findUserByHash($this->getUser());
        $token = $tokenManager->getOwnToken();
        $cryptoWEB = $cryptoManager->findBySymbol('WEB');
//        $cryptoBTC = $cryptoManager->findBySymbol('BTC');
        null == $token || null == $cryptoWEB
            ? $marketWEB = null
            : $marketWEB = new Market($cryptoWEB, $token);
//
//        null == $token || null == $cryptoBTC
//            ? $marketBTC = null
//            : $marketBTC = new Market($cryptoBTC, $token);

        $markets = [
            $marketWEB->getHiddenName() ?? null,
//            $marketBTC->getHiddenName() ?? null,
        ];

        return $this->render('pages/wallet.html.twig', [
                'hash' => $user->getHash(),
                'tokens' => $markets,
                'user_id' => $user->getId(),
        ]);
    }
}
