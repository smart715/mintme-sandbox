<?php declare(strict_types = 1);

namespace App\TwigExtension;

use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\WebpackEncoreBundle\Asset\EntrypointLookupInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class EncoreEntryCssExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    private ContainerInterface $container;
    private string $publicDir;

    public function __construct(ContainerInterface $container, ParameterBagInterface $params)
    {
        $this->container = $container;
        $this->publicDir = $params->get('public_dir');
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('encore_entry_css_source', [$this, 'getEncoreEntryCssSource']),
        ];
    }

    public function getEncoreEntryCssSource(string $entryName): string
    {
        $lookup = $this->container->get(EntrypointLookupInterface::class);
        $files = $lookup->getCssFiles($entryName);
        $source = '';

        foreach ($files as $file) {
            $source .= file_get_contents($this->publicDir.'/'.$file);
        }
        
        // allowing to send the same source files multiple times
        $lookup->reset();

        return $source;
    }

    public static function getSubscribedServices(): array
    {
        return [
            EntrypointLookupInterface::class,
        ];
    }
}
