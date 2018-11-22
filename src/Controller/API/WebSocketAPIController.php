<?php

namespace App\Controller\API;

use App\Entity\Crypto;
use App\Entity\User;
use App\Exchange\Market;
use App\Exchange\Order;
use App\Exchange\Trade\TraderInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\ProfileManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Utils\MarketNameParserInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;

/** @Rest\Route("/api/ws") */
class WebSocketAPIController extends FOSRestController
{
    /**
     * @Rest\Get("/auth", name="auth")
     * @Rest\View()
     */
    public function authUser(Request $request, ProfileManagerInterface $profileManager): View
    {
        $token = $request->headers->get('authorization');

        if (null == $token || is_array($token)) {
            return $this->error();
        }

        $user = $profileManager->findProfileByHash($token);

        if (null === $user) {
            return $this->error();
        }

        $profileManager->createHash($user, false);

        return $this->confirmed($user);
    }

    private function error(): View
    {
        return $this->view(
            [
                "error" =>
                    [
                        "code" => 5,
                        "message" => "service timeout",
                    ],
                "result" => null,
                "id" => 0,
            ]
        );
    }

    private function confirmed(User $user): View
    {
        return $this->view(
            [
                "code" => 0,
                "message" => null,
                "data" => ["user_id" => $user->getId()],
            ]
        );
    }
}
