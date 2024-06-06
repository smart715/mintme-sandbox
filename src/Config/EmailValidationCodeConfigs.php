<?php declare(strict_types = 1);

namespace App\Config;

class EmailValidationCodeConfigs extends ValidationCodeConfigs
{
    public const CURRENT_EMAIL = 'current_email_limits';
    public const NEW_EMAIL = 'new_email_limits';
    protected array $availableConfigs = [ //phpcs:ignore
        self::CURRENT_EMAIL, self::NEW_EMAIL,
    ];
}
