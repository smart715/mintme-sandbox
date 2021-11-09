<?php declare(strict_types = 1);

namespace App\TwigExtension;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AbsoluteUrlExtension extends AbstractExtension
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('absolute_url', [$this, 'doAbsoluteUrl']),
        ];
    }

    public function doAbsoluteUrl(string $value): ?string
    {
        if (0 !== strpos($value, 'http')) {
            $value = $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost() . $value;
        }

        return $value;
    }
}
