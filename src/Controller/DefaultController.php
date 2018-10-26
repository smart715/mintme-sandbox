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
}
