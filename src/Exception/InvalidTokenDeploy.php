<?php declare(strict_types = 1);

namespace App\Exception;

/** @codeCoverageIgnore */
class InvalidTokenDeploy extends \Exception
{
    /** @var string  */
    protected $message = 'Invalid token deploy';
}
