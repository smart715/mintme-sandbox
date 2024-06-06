<?php declare(strict_types = 1);

namespace App\Communications\SMS\Config;

class SmsConfig
{
    private array $smsConfig;
    private bool $disableSms;
    private array $enabledProviders = []; // phpcs:ignore

    public function __construct(array $smsConfig, bool $disableSms)
    {
        $this->smsConfig = $smsConfig;
        $this->disableSms = $disableSms;
        $this->prepareProviders();
    }

    public function getProviders(): array
    {
        return $this->enabledProviders;
    }

    public function hasProviders(): bool
    {
        return count($this->enabledProviders) > 0;
    }

    public function isSmsDisabled(): bool
    {
        return $this->disableSms;
    }

    private function prepareProviders(): void
    {
        foreach ($this->smsConfig as $providerKey => $providerData) {
            if (true === $providerData['enabled']) {
                $this->enabledProviders[$providerKey] = $providerData;
            }
        }

        $priority = [];

        foreach ($this->enabledProviders as $key => $row) {
            $priority[$key] = $row['priority'];
        }

        array_multisort($priority, SORT_ASC, $this->enabledProviders);
    }
}
