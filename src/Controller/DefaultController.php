<?php

namespace App\Controller;

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
    public function wallet(ProfileManagerInterface $profileManager, TokenManagerInterface $tokenManager
    ): Response
    {
        $user = $profileManager->findHash($this->getUser());
        $token = $tokenManager->getOwnToken();
        dump($token);
        return $this->render('pages/wallet.html.twig', [
            'hash' => $user->getHash(),
            'token' => $token->getName(),
        ]);
    }
}
