<?php declare(strict_types = 1);

namespace App\EventSubscriber;

use App\Entity\KnowledgeBase\KnowledgeBase;
use App\Entity\News\Post;
use App\Entity\Profile;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Manager\TokenManagerInterface;
use App\Utils\Converter\RebrandingConverterInterface;
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
    private $marketFactory;

    /** @var RebrandingConverterInterface */
    private $rebrandingConverter;

    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        EntityManagerInterface $entityManager,
        TokenManagerInterface $tokenRepository,
        MarketFactoryInterface $marketFactory,
        RebrandingConverterInterface $rebrandingConverter
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->entityManager = $entityManager;
        $this->tokenRepository = $tokenRepository;
        $this->marketFactory = $marketFactory;
        $this->rebrandingConverter = $rebrandingConverter;
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
        $this->registerKBUrls($event->getUrlContainer());
        $this->registerProfilesUrls($event->getUrlContainer());
        $this->registerTokensUrls($event->getUrlContainer());
        $this->registerMarketsUrls($event->getUrlContainer());
    }

    private function registerDefaultUrls(UrlContainerInterface $urls): void
    {
        $lastUrls = [
            $this->urlGenerator->generate(
                'sonata_news_home',
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

        /** @var Post $new */
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

    private function registerKBUrls(UrlContainerInterface $urls): void
    {
        $kbRepository = $this->entityManager->getRepository(KnowledgeBase::class);

        /** @var KnowledgeBase[] $kbs */
        $kbs = $kbRepository->findAll();

        foreach ($kbs as $kb) {
            $urls->addUrl(
                new UrlConcrete(
                    $this->urlGenerator->generate(
                        'kb_show',
                        ['url' => $kb->getUrl()],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    )
                ),
                'help'
            );
        }
    }

    private function registerProfilesUrls(UrlContainerInterface $urls): void
    {
        $profilesRepository = $this->entityManager->getRepository(Profile::class);

        $profiles = $profilesRepository->findAll();

        /** @var Profile $profile */
        foreach ($profiles as $profile) {
            $urls->addUrl(
                new UrlConcrete(
                    $this->urlGenerator->generate(
                        'profile-view',
                        ['nickname' => $profile->getNickname()],
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
        $markets = $this->marketFactory->getCoinMarkets();

        foreach ($markets as $market) {
            $urls->addUrl(
                new UrlConcrete(
                    $this->urlGenerator->generate(
                        'coin',
                        [
                            'base' => $market->getBase()->getSymbol(),
                            'quote' => $this->rebrandingConverter->convert(
                                $market->getQuote()->getSymbol()
                            ),
                        ],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    ) //$market->getBase()  $market->getQuote()
                ),
                'markets'
            );
        }
    }
}
