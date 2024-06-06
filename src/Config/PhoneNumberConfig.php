<?php declare(strict_types = 1);

namespace App\Config;

/** @codeCoverageIgnore  */
class PhoneNumberConfig
{
    private array $editPhoneConfig;

    public function __construct(array $editPhoneConfig)
    {
        $this->editPhoneConfig = $editPhoneConfig;
    }

    public function getEditPhoneAttempts(): int
    {
        return (int)$this->editPhoneConfig['attempts'];
    }

    public function getEditPhoneInterval(): string
    {
        return $this->editPhoneConfig['interval'];
    }
}
