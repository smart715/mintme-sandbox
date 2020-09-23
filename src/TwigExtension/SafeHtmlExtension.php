<?php declare(strict_types = 1);

namespace App\TwigExtension;

use HTMLPurifier;
use HTMLPurifier_Config;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class SafeHtmlExtension extends AbstractExtension
{
    /** @var HTMLPurifier */
    private $purifier;

    public function __construct()
    {
        $this->purifier = new HTMLPurifier(
            HTMLPurifier_Config::createDefault()
        );
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('safeHtml', [$this, 'doSafeHtml']),
        ];
    }

    public function doSafeHtml(string $value): ?string
    {
        return $this->purifier->purify($value);
    }
}
