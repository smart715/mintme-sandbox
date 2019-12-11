<?php declare(strict_types = 1);

namespace App\Utils\Converter;

class RebrandingConverter implements RebrandingConverterInterface
{
    public function convert(string $value): string
    {
        $regExp =   ['/(Webchain)/', '/(webchain)/', '/(WEB)/', '/(web)/'];
        $replacer = ['MintMe Coin',  'mintMe Coin',  'MINTME',  'MINTME'];

        return (string) preg_replace($regExp, $replacer, $value);
    }

    public function reverseConvert(string $value): string
    {
        $regExp =   ['/(MintMe Coin)/',  '/(mintMe Coin)/',  '/(MINTME)/',  '/(mintme)/'];
        $replacer = ['Webchain', 'webchain', 'WEB', 'WEB'];

        return (string) preg_replace($regExp, $replacer, $value);
    }
}
