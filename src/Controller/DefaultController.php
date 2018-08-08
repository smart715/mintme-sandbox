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
     * @Route("/profile/{name}", name="profile_view")
     */
    public function profileView(String $name): Response
    {
        return $this->render('default/profile_view.html.twig', [
            'name' => $name,
        ]);
    }

    /**
     * @Route("/token/{name}/{tab}", name="token")
     */
    public function token(?String $name = null, ?String $tab = null): Response
    {
        // FIXME: This data is for view test only.
        $tokenName = $name;
        $action = 'invest';
        $tab = strtolower(strval($tab));
        $name = strtolower(strval($name));
        if (empty($name) && empty($tab)) {
            $action = 'edit';
            if (empty($tokenName))
                $tokenName = 'Dummy Token Name';
        } elseif (!empty($tab)) {
            if ('invest' === $tab || 'intro' === $tab)
                $action = $tab;
        } elseif ('new' === $name) {
            $action = 'new';
            $tokenName = null;
        }

        return $this->render('default/token.html.twig', [
            'tokenName' => $tokenName,
            'action' => $action,
        ]);
    }
}
