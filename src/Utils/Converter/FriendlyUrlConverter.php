<?php declare(strict_types=1);

namespace App\Utils\Converter;

class FriendlyUrlConverter implements FriendlyUrlConverterInterface
{
    public function convert(string $name): string
    {
        $url = strtolower(trim($name));

        $find = [' ', '&', '\r\n', '\n', '+', ','];
        $url = str_replace($find, '-', $url);

        $find = ['/[^a-z0-9\-<>.]/', '/[\-]+/', '/<[^>]*>/'];
        $repl = ['', '-', ''];
        $url = preg_replace($find, $repl, $url);

        return $url;
    }
}
