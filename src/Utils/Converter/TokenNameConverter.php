<?php declare(strict_types = 1);

namespace App\Utils\Converter;

use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
use App\Exchange\Config\Config;

class TokenNameConverter implements TokenNameConverterInterface
{
    /** @var Config */
    private $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function convert(TradebleInterface $tradable): string
    {
        return $tradable instanceof Token
            ? 'TOK'.str_pad(
                (string)($tradable->getId() + $this->config->getOffset()),
                12,
                '0',
                STR_PAD_LEFT
            )
            : $tradable->getSymbol();
    }
}
