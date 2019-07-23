<?php declare(strict_types = 1);

namespace App\EventSubscriber;

use App\Entity\News\Post;
use App\Entity\Profile;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Manager\TokenManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Presta\SitemapBundle\Event\SitemapPopulateEvent;
use Presta\SitemapBundle\Service\UrlContainerInterface;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @codeCoverageIgnore
 */
class SitemapSubscriber implements EventSubscriberInterface
{
    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    /** @var EntityManagerInterface  */
    private $entityManager;

    /** @var TokenManagerInterface  */
    private $tokenRepository;

    /** @var MarketFactoryInterface  */
    private $MarketFactory;

    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        EntityManagerInterface $entityManager,
        TokenManagerInterface $tokenRepository,
        MarketFactoryInterface $MarketFactory
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->entityManager = $entityManager;
        $this->tokenRepository = $tokenRepository;
        $this->MarketFactory = $MarketFactory;
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
        $this->registerMarketsUrls($event->getUrlContainer());
    }

    private function registerDefaultUrls(UrlContainerInterface $urls): void
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

    private function registerNewsUrls(UrlContainerInterface $urls): void
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

    private function registerProfilesUrls(UrlContainerInterface $urls): void
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

    private function registerTokensUrls(UrlContainerInterface $urls): void
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
    private function registerMarketsUrls(UrlContainerInterface $urls): void
    {
        $markets = $this->MarketFactory->getCoinMarkets();

        foreach ($markets as $market) {
            $urls->addUrl(
                new UrlConcrete(
                    $this->urlGenerator->generate(
                        'coin',
                        ['base' => $market->getBase()->getSymbol(), 'quote' => $market->getQuote()->getSymbol()],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    ) //$market->getBase()  $market->getQuote()
                ),
                'markets'
            );
        }
    }
}
