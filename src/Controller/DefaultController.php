<?php

namespace App\Controller;

use App\Exchange\Market;
use App\Manager\CryptoManagerInterface;
use App\Manager\ProfileManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Repository\UserRepository;
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
    public function trading(): Response
    {
        return $this->render('pages/trading.html.twig');
    }

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
        $user = $profileManager->findUserByHash($this->getUser());
        $crypto = $cryptoManager->findBySymbol('WEB');
        $token = $tokenManager->getOwnToken();
        if (null !== $crypto && null !== $token) {
            $market = new Market($crypto, $token);
            return $this->render('pages/wallet.html.twig', [
                'hash' => $user->getHash(),
                'token' => $market->getHiddenName(),
                'user_id' => $user->getId(),
            ]);
        } else {
            return $this->redirectToRoute('fos_user_security_login');
        }
    }
}
