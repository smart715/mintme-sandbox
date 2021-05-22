<?php declare(strict_types = 1);

namespace App\Utils\Converter;

use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
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

    public function convert(TradebleInterface $tradable): string
    {
        return $tradable instanceof Token && !$this->cryptoManager->findBySymbol(strtoupper($tradable->getName()))
            ? 'TOK'.str_pad((string)($tradable->getId() + $this->config->getOffset()), 12, '0', STR_PAD_LEFT)
            : $tradable->getName();
    }
}
