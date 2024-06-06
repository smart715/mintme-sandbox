<?php declare(strict_types = 1);

namespace App\Exception;

use Symfony\Component\HttpFoundation\Response;

/** @codeCoverageIgnore */
class NotDeployedTokenException extends ApiException
{
    /** @var string  */
    protected $message = "API is only available for deployed tokens";

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}
