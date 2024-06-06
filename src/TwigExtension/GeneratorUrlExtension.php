<?php declare(strict_types = 1);

namespace App\TwigExtension;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class GeneratorUrlExtension extends AbstractExtension
{
    private UrlGeneratorInterface $generator;

    public function __construct(UrlGeneratorInterface $generator)
    {
        $this->generator = $generator;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('path', [$this, 'getPath']),
            new TwigFunction('url', [$this, 'getUrl']),
        ];
    }

    public function getPath(string $name, array $parameters = [], bool $relative = false): string
    {
        $pathKind = $relative
            ? UrlGeneratorInterface::RELATIVE_PATH
            : UrlGeneratorInterface::ABSOLUTE_PATH;

        return $this->generator->generate($name, $parameters, $pathKind);
    }

    public function getUrl(string $name, array $parameters = [], bool $schemeRelative = false): string
    {
        $urlKind = $schemeRelative
            ? UrlGeneratorInterface::NETWORK_PATH
            : UrlGeneratorInterface::ABSOLUTE_URL;

        return $this->generator->generate($name, $parameters, $urlKind);
    }
}
