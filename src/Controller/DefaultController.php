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
     * @Route("/token/{name}/{tab}", name="token")
     */
    public function token(?String $name = null, ?String $tab = null): Response
    {
        // FIXME: This is for view test only.
        $tokenName = $name;
        $action = 'invest';
        $tab = strtolower(strval($tab));
        $name = strtolower(strval($name));
        if (empty($name) && empty($tab)) {
            $action = 'edit';
            if (empty($tokenName)) {
                $tokenName = 'Dummy Token Name';
            }
        } elseif (!empty($tab)) {
            if ('invest' === $tab || 'intro' === $tab) {
                $action = $tab;
            }
        } elseif ('new' === $name) {
            $action = 'new';
            $tokenName = null;
        }

        return $this->render('pages/token.html.twig', [
            'tokenName' => $tokenName,
            'action' => $action,
        ]);
    }
}
