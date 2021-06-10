<?php declare(strict_types = 1);

namespace App\TwigExtension;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class IsEmbededExtension extends AbstractExtension
{
    public const EMBEDED_REGEX = '/.*\/embeded$/';

    private ?Request $request;

    public function __construct(RequestStack $requestStack)
    {
        $this->request = $requestStack->getCurrentRequest();
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('isEmbeded', [$this, 'isEmbeded']),
        ];
    }

    public function isEmbeded(): bool
    {
        return $this->request
            && (bool)preg_match(self::EMBEDED_REGEX, $this->request->getPathInfo());
    }
}
