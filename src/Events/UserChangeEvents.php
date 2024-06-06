<?php declare(strict_types = 1);

namespace App\Events;

/** @codeCoverageIgnore */
final class UserChangeEvents
{
    public const PASSWORD_UPDATED = 'PASSWORD_UPDATED';
    public const EMAIL_UPDATED = 'EMAIL_UPDATED';
    public const PHONE_UPDATED = 'PHONE_UPDATED';
    public const TWO_FACTOR_DISABLED = 'TWO_FACTOR_DISABLED';
    public const PASSWORD_UPDATED_MSG = 'toasted.success.password_updated';
}
