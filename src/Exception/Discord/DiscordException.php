<?php declare(strict_types = 1);

namespace App\Exception\Discord;

use GuzzleHttp\Command\Exception\CommandClientException;

/** @codeCoverageIgnore */
class DiscordException extends \Exception
{
    public function __construct(array $error, ?CommandClientException $previous)
    {
        parent::__construct($error['message'] ?? '', $error['code'] ?? 0, $previous);
    }
}
