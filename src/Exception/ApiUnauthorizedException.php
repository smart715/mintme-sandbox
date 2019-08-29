<?php declare(strict_types = 1);

namespace App\Exception;

use Symfony\Component\HttpFoundation\Response;

/** @codeCoverageIgnore  */
class ApiUnauthorizedException extends ApiException
{
    public function getStatusCode(): int
    {
        return Response::HTTP_UNAUTHORIZED;
    }
}
