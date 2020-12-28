<?php declare(strict_types = 1);

namespace App\Exception;

use Symfony\Component\HttpFoundation\Response;

/** @codeCoverageIgnore  */
class ApiBadForbiddenException extends ApiException
{
    public function getStatusCode(): int
    {
        return Response::HTTP_FORBIDDEN;
    }
}
