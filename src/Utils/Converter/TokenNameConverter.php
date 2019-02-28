<?php

namespace App\Utils\Converter;

use App\Entity\Token\Token;
use App\Exchange\Config\Config;
use App\Manager\CryptoManagerInterface;

class TokenNameConverter implements TokenNameConverterInterface
{
    /** @var CryptoManagerInterface */
    private $cryptoManager;

    /** @var Config */
    private $config;

    public function __construct(CryptoManagerInterface $cryptoManager, Config $config)
    {
        $this->cryptoManager = $cryptoManager;
        $this->config = $config;
    }

    public function convert(Token $token): string
    {
        return !$this->cryptoManager->findBySymbol(strtoupper($token->getName() ?? ''))
            ? 'TOK'.str_pad((string)($token->getId() + $this->config->getOffset()), 12, '0', STR_PAD_LEFT)
            : (string)$token->getName();
    }
}
