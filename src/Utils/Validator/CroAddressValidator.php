<?php declare(strict_types = 1);

namespace App\Utils\Validator;

use App\Config\PanelEnvConfig;

/*
 * This implementation is for Crypto.org chain, which we won't support due to Cronos chain
 * This code is unused but kept in case of future need
 *
 * Cronos chain is ethereum compatible, so it goes throught our ethereum based classes/logic
 */
class CroAddressValidator implements ValidatorInterface
{
    private string $address;
    
    private string $message = 'Invalid cro address'; //phpcs:ignore
    
    private string $panelEnvironment;
    
    public function __construct(string $address, string $panelEnvironment)
    {
        $this->address = $address;
        $this->panelEnvironment = $panelEnvironment;
    }
    
    public function validate(): bool
    {
        $panelEnvConfig = new PanelEnvConfig($this->panelEnvironment);

        return 0 === strpos(
            $this->address,
            $panelEnvConfig->isProd() ? 'cro' : 'tcro'
        ) &&
            ($panelEnvConfig->isProd() ? 42 : 43) === strlen($this->address);
    }
    
    public function getMessage(): string
    {
        return $this->message;
    }
}
