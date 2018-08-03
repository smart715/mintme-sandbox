<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function index(): Response
    {
        return $this->render('default/index.html.twig');
    }

    /**
     * @Route("/trading", name="trading")
     */
    public function trading(): Response
    {
        return $this->render('default/trading.html.twig');
    }

    /**
     * @Route("/wallet", name="wallet")
     */
    public function wallet(): Response
    {
        return $this->render('default/wallet.html.twig');
    }

    /**
     * @Route("/profile", name="profile")
     */
    public function profile(): Response
    {
        return $this->render('default/profile.html.twig');
    }

    /**
     * @Route("/token", name="token")
     */
    public function token(): Response
    {
        return $this->render('default/token.html.twig');
    }

    /**
     * @Route("/my-token", name="my_token")
     */
    public function myToken(): Response
    {
        return $this->render('default/my_token.html.twig');
    }

    /**
     * @Route("/profile/{name}", name="profile_view")
     */
    public function profileView(String $name): Response
    {
        return $this->render('default/profile_view.html.twig', [
            'name' => $name,
        ]);
    }
}
