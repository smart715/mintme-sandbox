<?php declare(strict_types = 1);

namespace App\EventSubscriber;

use App\Entity\KnowledgeBase\KnowledgeBase;
use App\Entity\News\Post;
use App\Entity\Profile;
use App\Entity\Token\Token;
use App\Entity\Voting\Voting;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Repository\KnowledgeBase\KnowledgeBaseRepository;
use App\Repository\News\PostRepository;
use App\Repository\ProfileRepository;
use App\Utils\Converter\RebrandingConverterInterface;
use App\Utils\Symbols;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Internal\Hydration\IterableResult;
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
    private const API_AREAS = ["v1", "v2"];

    private UrlGeneratorInterface $urlGenerator;

    private EntityManagerInterface $entityManager;

    private TokenManagerInterface $tokenRepository;

    private MarketFactoryInterface $marketFactory;

    private RebrandingConverterInterface $rebrandingConverter;

    private CryptoManagerInterface $cryptoManager;

    private PostRepository $postRepository;

    private KnowledgeBaseRepository $knowledgeBaseRepository;

    private ProfileRepository $profileRepository;

    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        EntityManagerInterface $entityManager,
        TokenManagerInterface $tokenRepository,
        MarketFactoryInterface $marketFactory,
        RebrandingConverterInterface $rebrandingConverter,
        CryptoManagerInterface $cryptoManager,
        PostRepository $postRepository,
        KnowledgeBaseRepository $knowledgeBaseRepository,
        ProfileRepository $profileRepository
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->entityManager = $entityManager;
        $this->tokenRepository = $tokenRepository;
        $this->marketFactory = $marketFactory;
        $this->rebrandingConverter = $rebrandingConverter;
        $this->cryptoManager = $cryptoManager;
        $this->postRepository = $postRepository;
        $this->knowledgeBaseRepository = $knowledgeBaseRepository;
        $this->profileRepository = $profileRepository;
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
        $this->registerVotingsUrls($event->getUrlContainer());
        $this->registerAPIUrls($event->getUrlContainer());
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
                    $url
                ),
                'default'
            );
        }
    }

    private function registerNewsUrls(UrlContainerInterface $urls): void
    {
        $iterable = $this->postRepository->createQueryBuilder('p')->getQuery()->iterate();

        $this->batchProcessingDecorator(function (Post $post) use ($urls): void {
            $urls->addUrl(
                new UrlConcrete(
                    $this->urlGenerator->generate(
                        'sonata_news_view',
                        ['permalink' => $post->getSlug()],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    ),
                ),
                'news'
            );
        }, $iterable);
    }

    private function registerKBUrls(UrlContainerInterface $urls): void
    {
        $iterable = $this->knowledgeBaseRepository->createQueryBuilder('kb')->getQuery()->iterate();

        $this->batchProcessingDecorator(function (KnowledgeBase $kb) use ($urls): void {
            $urls->addUrl(
                new UrlConcrete(
                    $this->urlGenerator->generate(
                        'kb_show',
                        ['url' => $kb->getUrl()],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    ),
                ),
                'help'
            );
        }, $iterable);
    }

    private function registerProfilesUrls(UrlContainerInterface $urls): void
    {
        $iterable = $this->profileRepository->createQueryBuilder('p')
            ->leftJoin('p.user', 'u')
            ->where('u.isBlocked = false')
            ->getQuery()
            ->iterate();

        $this->batchProcessingDecorator(function (Profile $profile) use ($urls): void {
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
        }, $iterable);
    }

    private function batchProcessingDecorator(callable $callback, IterableResult $iterable): void
    {
        $counter = 0;

        while (false !== ($entity = $iterable->next())) {
            $callback($entity[0]);

            $counter++;

            if (0 === $counter % 100) {
                $this->entityManager->clear();
            }
        }
    }

    private function registerTokensUrls(UrlContainerInterface $urls): void
    {
        $queryBuilder = $this->tokenRepository->getRepository()
            ->createQueryBuilder('token')
            ->distinct()
            ->leftJoin('token.deploys', 'deploys')
            ->where('token.deployed = true AND token.isBlocked = false')
            ->getQuery();
        $iterable = $queryBuilder->iterate();

        while (false !== ($entity = $iterable->next())) {
            /** @var Token $token */
            $token = $entity[0];
            $urls->addUrl(
                new UrlConcrete(
                    $this->urlGenerator->generate(
                        'token_show_intro',
                        ['name' => $token->getName()],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    )
                ),
                'tokens'
            );

            $votings = $token->getVotings();

            foreach ($votings as $voting) {
                /** @var Voting $voting */
                $urls->addUrl(
                    new UrlConcrete(
                        $this->urlGenerator->generate(
                            'token_show_voting',
                            ['name' => $token->getName(), 'slug' => $voting->getSlug()],
                            UrlGeneratorInterface::ABSOLUTE_URL
                        ),
                        $voting->getCreatedAt()
                    ),
                    'token-votings'
                );
            }

            $this->entityManager->clear();
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

    private function registerVotingsUrls(UrlContainerInterface $urls): void
    {
        $mintme = $this->cryptoManager->findBySymbol(Symbols::WEB);

        foreach ($mintme->getVotings() as $voting) {
            /** @var Voting $voting */
            $urls->addUrl(
                new UrlConcrete(
                    $this->urlGenerator->generate(
                        'show_voting',
                        ['slug' => $voting->getSlug()],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    ),
                    $voting->getCreatedAt()
                ),
                'votings'
            );
        }
    }

    private function registerAPIUrls(UrlContainerInterface $urls): void
    {
        foreach (self::API_AREAS as $area) {
            $urls->addUrl(
                new UrlConcrete(
                    $this->urlGenerator->generate(
                        'app.swagger_ui',
                        ['area' => $area],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    )
                ),
                'api'
            );
        }
    }
}
