<?php declare(strict_types = 1);

namespace App\EventListener;

use App\Manager\TokenManagerInterface;
use Presta\SitemapBundle\Event\SitemapPopulateEvent;
use Presta\SitemapBundle\Service\UrlContainerInterface;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SitemapTokenListener
{
    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    /** @var TokenManagerInterface */
    private $tokenRepository;

    public function __construct(UrlGeneratorInterface $urlGenerator, TokenManagerInterface $tokenRepository)
    {
        $this->urlGenerator = $urlGenerator;
        $this->tokenRepository = $tokenRepository;
    }

    public function onPrestasitemapPopulate(SitemapPopulateEvent $event): void
    {
        $this->registerTokensUrls($event->getUrlContainer());
    }
    public function registerTokensUrls(UrlContainerInterface $urls): void
    {
        $tokens = $this->tokenRepository->findAll();

        foreach ($tokens as $token) {
            $urls->addUrl(
                new UrlConcrete(
                    $this->urlGenerator->generate(
                        'token_show',
                        ['name' => $token->getName()],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    )
                ),
                'tokens'
            );
        }
    }
}
