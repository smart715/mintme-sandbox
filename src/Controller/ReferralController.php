<?php

namespace App\Controller;

use App\Entity\User;
use App\Manager\UserManagerInterface;
use DateInterval;
use DateTime;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ReferralController extends AbstractController
{
       
    private const REFERRAL_COOKIE_EXPIRE_DAYS = 7;

    /**
     * @Route("/referral", name="referral")
     */
    public function referral(UserManagerInterface $userManager): Response
    {
        if (null  === $this->getUser()) {
            return $this->redirect('/login');
        }
        return $this->render('pages/referral.html.twig', [
            'referralCode' => $this->getUser()->getReferralCode(),
            'referralsTotal' => $userManager->getReferencesTotal(intval($this->getUser()->getId())),
        ]);
    }

    /**
     * @Route("/invite/{referralCode}", name="register_referral", requirements={"referralCode" = "^[0-9a-f]{8}(-[0-9a-f]{4}){3}-[0-9a-f]{12}$"})
     */
    public function registerReferral(Request $request, string $referralCode): Response
    {
        $response = $this->redirectToRoute('fos_user_registration_register');
        $response->headers->setCookie($this->createReferralCookie($referralCode));
        $request->getSession()->set('referral', $referralCode);
        return $response;
    }
    
    private function createReferralCookie(string $referralCode): Cookie
    {
        $cookieExpireTime = new DateTime();
        $cookieExpireTime->add(new DateInterval(
            'P'.self::REFERRAL_COOKIE_EXPIRE_DAYS.'D'
        ));
        return new Cookie('referral', $referralCode, $cookieExpireTime);
    }
}
