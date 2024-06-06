<?php declare(strict_types = 1);

namespace App\Exception;

/** @codeCoverageIgnore */
class UserTokenFollowException extends \Exception
{
    public const USER_IS_OWNER = 1;
    public const NOT_FIRST_FOLLOW = 2;
}
