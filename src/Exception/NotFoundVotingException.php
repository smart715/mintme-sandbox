<?php declare(strict_types = 1);

namespace App\Exception;

use Symfony\Component\HttpFoundation\Response;

/** @codeCoverageIgnore  */
class NotFoundVotingException extends \Exception
{
    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }
}
