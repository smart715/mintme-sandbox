<?php

namespace App\Controller;

use App\Entity\User;
use App\Manager\ProfileManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AuthAPIController
{
    public function authUser(ProfileManagerInterface $profileManager, LoggerInterface $logger, Request $request): JsonResponse
    {
        $token = $request->headers->get('authorization');
        $token = trim(str_replace('Basic', '', $token));
        $user = $profileManager->validateUserApi($token);
        $debug = (string)json_encode($token);
        $logger->alert($debug);
        return $user
            ? $this->confirmed($user)
            : $this->error() ;
    }

    private function confirmed(User $user): JsonResponse
    {
        return new JsonResponse(
            [
                "code" => 0,
                "message" => null,
                "data" => ["user_id" => $user->getId()],
            ]
        );
    }

    private function error(): JsonResponse
    {
        return new JsonResponse(
            [
                "error" =>
                    [
                        "code" => 5,
                        "message" => "service timeout",
                    ],
                "result"=> null,
                "id" => 0,
            ]
        );
    }
}
