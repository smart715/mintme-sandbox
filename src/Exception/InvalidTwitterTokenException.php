<?php declare(strict_types = 1);

namespace App\Exception;

/** @codeCoverageIgnore */
class InvalidTwitterTokenException extends \Exception
{
    /** @var string  */
    protected $message = 'invalid twitter token';
}
