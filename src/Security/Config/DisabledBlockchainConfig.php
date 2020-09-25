<?php declare(strict_types = 1);

namespace App\Security\Config;

class DisabledBlockchainConfig
{
    /** @var array<string>|null $disabledCrypto */
    private $disabledCrypto;

    public function __construct(?array $disabledCrypto)
    {
        $this->disabledCrypto = $disabledCrypto;
    }

    public function getDisabledCryptoSymbols(): array
    {
        return $this->disabledCrypto ?? [];
    }
}
