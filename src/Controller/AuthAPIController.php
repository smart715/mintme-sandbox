<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;

class AuthAPIController
{
    public function authUser(): JsonResponse {
        var_dump(apache_request_headers());
        return new JsonResponse(
            [
                "code" => 0,
                "message" => null,
                "data" => ["user_id" => 1],
            ]
        );
    }

}