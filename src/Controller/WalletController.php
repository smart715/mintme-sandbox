<?php

namespace App\Controller;

use App\Exchange\Market;
use App\Manager\CryptoManagerInterface;
use App\Manager\ProfileManagerInterface;
use App\Manager\TokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
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
        $cryptoBTC = $cryptoManager->findBySymbol('BTC');
        if ($cryptoWEB!= null && $token != null){
            $marketWEB = new Market($cryptoWEB, $token);
        } else {
            $marketWEB = null;
        }

        $marketBTC = $cryptoBTC || $token
            ? new Market($cryptoBTC, $token)
            : null;
        $markets = [
            $marketWEB ? $marketWEB->getHiddenName() : null,
            $marketBTC ? $marketBTC->getHiddenName() : null,
        ];

        return $this->render('pages/wallet.html.twig', [
                'hash' => $user->getHash(),
                'tokens' => $markets,
                'user_id' => $user->getId(),
        ]);
    }
}
