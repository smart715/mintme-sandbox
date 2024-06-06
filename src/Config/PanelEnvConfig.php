<?php declare(strict_types = 1);

namespace App\Config;

class PanelEnvConfig
{
    private const DEV_MODE = 'dev';
    private const PROD_MODE = 'prod';
    private string $panelEnvironment;
    
    public function __construct(string $panelEnvironment)
    {
        $this->panelEnvironment = $panelEnvironment;
    }
    
    public function isDev(): bool
    {
        return self::DEV_MODE === $this->panelEnvironment;
    }
    
    public function isProd(): bool
    {
        return self::PROD_MODE === $this->panelEnvironment;
    }
}
