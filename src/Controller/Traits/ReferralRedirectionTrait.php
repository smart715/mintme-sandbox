<?php declare(strict_types = 1);

namespace App\Controller\Traits;

use App\Controller\TokenController;
use App\Manager\AirdropReferralCodeManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Manager\UserManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

trait ReferralRedirectionTrait
{
    private UserManagerInterface $userManager;
    private AirdropReferralCodeManagerInterface $arcManager;

    protected function referralRedirect(Request $request, TokenManagerInterface $tokenManager): ?RedirectResponse
    {
        $referralCode = $request->cookies->get('referral-code');
        $referralType = $request->cookies->get('referral-type');
        $referralToken = $request->cookies->get('referral-token');

        if (null === $referralCode || null === $referralType) {
            return null;
        }

        switch ($referralType) {
            case TokenController::TOKEN_REFERRAL_TYPE:
                $referrerUser = $this->userManager->findByReferralCode($referralCode);

                if (!$referrerUser) {
                    $token = null;

                    break;
                }

                $token = $referralToken
                    ? $tokenManager->findByName($referralToken)
                    : $referrerUser->getProfile()->getFirstToken();

                break;
            case TokenController::AIRDROP_REFERRAL_TYPE:
                $arc = $this->arcManager->decode($referralCode);
                $token = $arc
                    ? $arc->getAirdrop()->getToken()
                    : null;

                break;
        }

        if (isset($token)) {
            $response = $this->redirectToRoute("token_show_intro", ["name" => $token->getName()]);
            $response->headers->clearCookie('referral-code');
            $response->headers->clearCookie('referral-type');
            $response->headers->clearCookie('referral-token');

            return $response;
        }

        return null;
    }
}
