<?php declare(strict_types = 1);

namespace App\Utils\Converter;

use App\Entity\Token\Token;
use App\Entity\TradableInterface;
use App\Exchange\Config\Config;

class TokenNameConverter implements TokenNameConverterInterface
{
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function convert(TradableInterface $tradable): string
    {
        return $tradable instanceof Token
            ? $this->convertId($tradable->getId())
            : $tradable->getMoneySymbol();
    }

    public function convertId(int $tokenId): string
    {
        return 'TOK'.str_pad(
            (string)($tokenId + $this->config->getOffset()),
            12,
            '0',
            STR_PAD_LEFT
        );
    }

    public function parseConvertedId(string $tokenName): int
    {
        return (int)filter_var($tokenName, FILTER_SANITIZE_NUMBER_INT) - $this->config->getOffset();
    }
}
