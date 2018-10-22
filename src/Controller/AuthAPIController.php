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
        $logger->alert((string)json_encode($_SERVER));
        $token = $request->headers->get('authorization');
        $user = $profileManager->validateUserApi($token);

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
