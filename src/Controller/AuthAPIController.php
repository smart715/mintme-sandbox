<?php

namespace App\Controller;

use App\Entity\User;
use App\Manager\ProfileManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AuthAPIController
{
    public function authUser(Request $request, LoggerInterface $logger): JsonResponse
    {
        $logger->alert((string)json_encode($request->headers->all()));
        return new JsonResponse(
            [
                "code" => 0,
                "message" => null,
                "data" => ["user_id" => 1],
            ]
        );
    }
}
