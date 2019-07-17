<?php declare(strict_types = 1);

namespace App\EventListener;

use Presta\SitemapBundle\Event\SitemapPopulateEvent;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SitemapDefaultListener
{
    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public function onPrestaSitemapPopulate(SitemapPopulateEvent $event): void
    {
        $urls = [
            $this->urlGenerator->generate(
                'homepage',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            $this->urlGenerator->generate(
                'trading',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            $this->urlGenerator->generate(
                'sonata_news_archive',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            $this->urlGenerator->generate(
                'fos_user_security_login',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            $this->urlGenerator->generate(
                'fos_user_registration_register',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            $this->urlGenerator->generate(
                'terms_of_service',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            $this->urlGenerator->generate(
                'privacy_policy',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            $this->urlGenerator->generate(
                'coin',
                ['base' => 'BTC', 'quote' => 'WEB'],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
        ];

        foreach ($urls as $url) {
            $event->getUrlContainer()->addUrl(
                new UrlConcrete(
                    $url,
                    new \DateTime(),
                    UrlConcrete::CHANGEFREQ_DAILY,
                    1
                ),
                'default'
            );
        }
    }
}
