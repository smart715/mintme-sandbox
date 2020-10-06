<?php declare(strict_types = 1);

namespace App\Utils;

class EscapeHtmlControlCharacters
{
    private const UNSAFE = [
        '&',
        "<",
        '>',
        '"',
        "'",
        '/',
    ];

    private const REPLACE_WITH = [
        '&amp;',
        '&lt;',
        '&gt;',
        '&quot;',
        '&#x27;',
        '&#x2F;',
    ];

    public static function encode(string $unescaped): string
    {
        return str_replace(self::UNSAFE, self::REPLACE_WITH, $unescaped);
    }
}
