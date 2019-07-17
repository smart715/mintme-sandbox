<?php declare(strict_types = 1);

namespace App\EventListener;

use App\Entity\News\Post;
use Doctrine\ORM\EntityManagerInterface;
use Presta\SitemapBundle\Event\SitemapPopulateEvent;
use Presta\SitemapBundle\Service\UrlContainerInterface;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SitemapNewsListener
{
    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(UrlGeneratorInterface $urlGenerator, EntityManagerInterface $entityManager)
    {
        $this->urlGenerator = $urlGenerator;
        $this->entityManager = $entityManager;
    }

    public function onPrestasitemapPopulate(SitemapPopulateEvent $event): void
    {
        $this->registerNewsUrls($event->getUrlContainer());
    }
    public function registerNewsUrls(UrlContainerInterface $urls): void
    {
        $newsRepository = $this->entityManager->getRepository(Post::class);

        $news = $newsRepository->findAll();

        foreach ($news as $new) {
            $urls->addUrl(
                new UrlConcrete(
                    $this->urlGenerator->generate(
                        'sonata_news_view',
                        ['permalink' => $new->getSlug()],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    )
                ),
                'news'
            );
        }
    }
}
