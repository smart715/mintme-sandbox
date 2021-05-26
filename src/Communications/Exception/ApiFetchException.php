<?php declare(strict_types = 1);

namespace App\Communications\Exception;

use App\Exception\ApiException;
use Symfony\Component\HttpFoundation\Response;

class ApiFetchException extends ApiException
{
    public function getStatusCode(): int
    {
        return Response::HTTP_SERVICE_UNAVAILABLE;
    }
}
