<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/wallet")
 * @Security(expression="is_granted('prelaunch')")
 */
class WalletController extends AbstractController
{
    /**
     * @Route(name="wallet")
     */
    public function wallet(): Response
    {
        return $this->render('pages/wallet.html.twig', [
            'hash' => $this->getUser()->getHash(),
        ]);
    }
}
