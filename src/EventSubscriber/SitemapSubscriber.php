<?php declare(strict_types = 1);

namespace App\EventSubscriber;

use App\Entity\News\Post;
use App\Entity\Profile;
use App\Manager\TokenManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Presta\SitemapBundle\Event\SitemapPopulateEvent;
use Presta\SitemapBundle\Service\UrlContainerInterface;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SitemapSubscriber implements EventSubscriberInterface
{
    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    /** @var EntityManagerInterface  */
    private $entityManager;

    /** @var TokenManagerInterface  */
    private $tokenRepository;

    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        EntityManagerInterface $entityManager,
        TokenManagerInterface $tokenRepository
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->entityManager = $entityManager;
        $this->tokenRepository = $tokenRepository;
    }

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return [
            SitemapPopulateEvent::ON_SITEMAP_POPULATE => 'populate',
        ];
    }

    public function populate(SitemapPopulateEvent $event): void
    {
        $this->registerDefaultUrls($event->getUrlContainer());
        $this->registerNewsUrls($event->getUrlContainer());
        $this->registerProfilesUrls($event->getUrlContainer());
        $this->registerTokensUrls($event->getUrlContainer());
    }

    public function registerDefaultUrls(UrlContainerInterface $urls): void
    {
        $lastUrls = [
            $this->urlGenerator->generate(
                'sonata_news_archive',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            $this->urlGenerator->generate(
                'fos_user_registration_register',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            $this->urlGenerator->generate(
                'coin',
                ['base' => 'BTC', 'quote' => 'WEB'],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
        ];

        foreach ($lastUrls as $url) {
            $urls->addUrl(
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

    public function registerProfilesUrls(UrlContainerInterface $urls): void
    {
        $profilesRepository = $this->entityManager->getRepository(Profile::class);

        $profiles = $profilesRepository->findAll();

        foreach ($profiles as $profile) {
            $urls->addUrl(
                new UrlConcrete(
                    $this->urlGenerator->generate(
                        'profile-view',
                        ['pageUrl' => $profile->getPageUrl()],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    )
                ),
                'profiles'
            );
        }
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
