<?php

namespace App\Controller;

use App\Manager\ProfileManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class AuthAPIController
{
    public function authUser(ProfileManagerInterface $profileManager): JsonResponse
    {
        $headers = apache_request_headers();
        $hash = $headers['Authorization'] ?? null;
        $user = $profileManager->validateUserApi($hash);
        if(null !== $user) {
            return new JsonResponse(
                [
                    "code" => 0,
                    "message" => null,
                    "data" => ["user_id" => $user->getId()],
                ]
            );
        } else {
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

}
