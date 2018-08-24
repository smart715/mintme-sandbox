<?php

namespace App\Controller;

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
     */
    public function wallet(): Response
    {
        return $this->render('pages/wallet.html.twig');
    }

    /**
     * @Route("/profile", name="profile")
     */
    public function profile(): Response
    {
        return $this->render('pages/profile.html.twig');
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
