<?php declare(strict_types = 1);

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
            : $token->getName();
    }

    public static function parse(?string $name): string
    {
        if (!$name) {
            return '';
        }

        while (!ctype_alnum(substr($name, 0, 1)) || !ctype_alnum(substr($name, -1))) {
            $name = trim($name);
            $name = trim($name, '-');
        }

        $name = (string)preg_replace(['/\s+/', '/\s*\-{1,}\s*/'], [' ', '-'], $name);

        return $name;
    }

    public static function dashedName(?string $name): string
    {
        return str_replace(' ', '-', self::parse($name));
    }
}
