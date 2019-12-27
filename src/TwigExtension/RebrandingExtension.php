<?php declare(strict_types = 1);

namespace App\TwigExtension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class RebrandingExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('rebranding', [$this, 'doRebranding']),
        ];
    }

    public function doRebranding(string $value): ?string
    {
        $regExp =   ['/(Webchain)/', '/(webchain)/', '/(WEB)/', '/(web)/'];
        $replacer = ['MintMe Coin',  'mintMe Coin',  'MINTME',  'MINTME'];

        return preg_replace($regExp, $replacer, $value);
    }
}
