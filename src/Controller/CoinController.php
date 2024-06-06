<?php declare(strict_types = 1);

namespace App\Controller;

use App\Entity\User;
use App\Exception\NotFoundPairException;
use App\Exchange\Config\MarketPairsConfig;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\MarketStatusManagerInterface;
use App\Security\Config\DisabledBlockchainConfig;
use App\Security\Config\DisabledServicesConfig;
use App\Services\TranslatorService\TranslatorInterface;
use App\Utils\BaseQuote;
use App\Utils\Converter\RebrandingConverterInterface;
use App\Utils\Symbols;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @Route("/coin")
 */
class CoinController extends Controller
{
    private CryptoManagerInterface $cryptoManager;

    private MarketFactoryInterface $marketFactory;

    private RebrandingConverterInterface $rebrandingConverter;

    private MarketStatusManagerInterface $marketStatusManager;

    private DisabledServicesConfig $disabledServicesConfig;

    private DisabledBlockchainConfig $disabledBlockchainConfig;

    private MarketPairsConfig $marketPairsConfig;

    private const KEY_FACTS_AMOUNT = 9;

    private const ROAD_MAP_SECTIONS_AMOUNT = 2;
    private const COIN_PAGE_URLS = [
        'whitePaperUrl' => 'https://github.com/webchain-network/wiki/wiki/White-Paper',
        'bitcoinTalkUrl' => 'https://bitcointalk.org/index.php?topic=3649170.0',
        'getWalletUrl' => 'https://github.com/mintme-com/wallet/releases',
        'coinimpUrl' => 'https://coinimp.com/',
    ];

    public function __construct(
        NormalizerInterface $normalizer,
        CryptoManagerInterface $cryptoManager,
        MarketFactoryInterface $marketFactory,
        RebrandingConverterInterface $rebrandingConverter,
        MarketStatusManagerInterface $marketStatusManager,
        DisabledServicesConfig $disabledServicesConfig,
        DisabledBlockchainConfig $disabledBlockchainConfig,
        MarketPairsConfig $marketPairsConfig
    ) {
        parent::__construct($normalizer);

        $this->cryptoManager = $cryptoManager;
        $this->marketFactory = $marketFactory;
        $this->rebrandingConverter = $rebrandingConverter;
        $this->marketStatusManager = $marketStatusManager;
        $this->disabledServicesConfig = $disabledServicesConfig;
        $this->disabledBlockchainConfig = $disabledBlockchainConfig;
        $this->marketPairsConfig = $marketPairsConfig;
    }
    /**
     * @Route("/{quote}/{base}/{modal}",
     *      name="coin",
     *      defaults={"quote"="MINTME"},
     *      options={"expose"=true,"2fa_progress"=false}),
     *      requirements={"modal"="signup"}
     * */
    public function pair(string $base, string $quote, ?string $modal = null): Response
    {
        $convertedOldUrl = $this->convertOldUrl($base, $quote);

        if ($convertedOldUrl) {
            $base = $convertedOldUrl['base'];
            $quote = $convertedOldUrl['quote'];
        }

        $base = $this->rebrandingConverter->reverseConvert($base);
        $quote = $this->rebrandingConverter->reverseConvert($quote);

        $baseCrypto = $this->cryptoManager->findBySymbol($base);
        $quoteCrypto = $this->cryptoManager->findBySymbol($quote);

        if (null === $baseCrypto || null === $quoteCrypto) {
            throw new NotFoundPairException();
        }

        $market = $this->marketFactory->create($baseCrypto, $quoteCrypto);

        if (!$this->marketStatusManager->isValid($market, true)) {
            throw new NotFoundPairException();
        }

        if ($convertedOldUrl) {
            return $this->redirectToRoute('coin', [
                'base' => $convertedOldUrl['base'],
                'quote' => $convertedOldUrl['quote'],
            ]);
        }

        /** @var User|null $user */
        $user = $this->getUser();

        $market = BaseQuote::reverseMarket($market);
        $index = $market->getBase()->getSymbol();

        $cryptos = $this->cryptoManager->findAllIndexed('symbol');
        $enabledMarketPairs = $this->marketPairsConfig->getEnabledPairsByQuote($market->getQuote()->getSymbol());
        $markets = array_reduce($enabledMarketPairs, function ($acc, $pair) use ($quoteCrypto, $cryptos) {
            $base = $pair['base'];

            if (isset($cryptos[$base])) {
                $acc[$base] = $this->marketFactory->create($cryptos[$base], $quoteCrypto);
            }

            return $acc;
        }, []);

        return $this->render('pages/pair.html.twig', [
            'currentMarket' => $this->normalize($market),
            'currentMarketIndex' => $index,
            'markets' => $this->normalize($markets),
            'isOwner' => false,
            'showTrade' => true,
            'hash' => $user ? $user->getHash() : '',
            'precision' => $quoteCrypto->getShowSubunit(),
            'isTokenPage' => false,
            'tab' => 'trade',
            'disabledServicesConfig' => $this->normalize($this->disabledServicesConfig),
            'disabledBlockchain' => $this->disabledBlockchainConfig->getDisabledCryptoSymbols(),
            'postRewardsCollectableDays' => $this->getParameter('post_rewards_collectable_days'),
            'isAuthorizedForReward' => $this->isGranted('collect-reward'),
            'showCreatedModal' => false,
            'enabledCryptos' => $this->normalize($this->cryptoManager->findAll()),
            'tradesDisabled' => $this->disabledServicesConfig->isCryptoTradesDisabled($index),
        ]);
    }

    /** @Route(name="coin_page", options={"expose"=true})*/
    public function coinPage(TranslatorInterface $translator): Response
    {
        $translations = [
            'keyFacts' => [],
            'roadMapCheckPoints' => [],
        ];

        for ($number = 1; $number <= self::KEY_FACTS_AMOUNT; $number++) {
            $translations['keyFacts'][$number]['header'] =
                $translator->trans('page.coin.key_fact.header.' . $number);

            $translations['keyFacts'][$number]['body'] =
                $translator->trans('page.coin.key_fact.body.' . $number);
        }

        for ($number = 1; $number <= self::ROAD_MAP_SECTIONS_AMOUNT; $number++) {
            $translations['roadMapCheckPoints'][$number]['header'] =
                $translator->trans('page.coin.roadmap.check_point.header.' . $number);

            $translations['roadMapCheckPoints'][$number]['body'] =
                $translator->trans('page.coin.roadmap.check_point.body.' . $number);
        }

        return $this->render('pages/coin.html.twig', [
            'urls' => self::COIN_PAGE_URLS,
            'translations' => $translations,
        ]);
    }

    /** @Route("/faq", name="coin_faq_page", options={"expose"=true}) */
    public function coinFaqPage(TranslatorInterface $translator): Response
    {
        return $this->render('pages/coin_faq.html.twig');
    }

    /** @Route("/start", name="coin_start_page", options={"expose"=true}) */
    public function coinStartPage(TranslatorInterface $translator): Response
    {
        return $this->render('pages/coin_start.html.twig', [
            'urls' => self::COIN_PAGE_URLS,
        ]);
    }

    /** @Route("/news", name="coin_news_page", options={"expose"=true}) */
    public function coinNewsPage(): Response
    {
        return $this->redirectToRoute('sonata_news_home');
    }

    private function convertOldUrl(string $base, string $quote): ?array
    {
        $upperCaseBase = mb_strtoupper($base);
        $upperCaseQuote = mb_strtoupper($quote);

        // if reversed base/quote order and web instead of mintme
        if (Symbols::WEB === $upperCaseBase) {
            return [
                'base' => $upperCaseQuote,
                'quote' => $this->rebrandingConverter->convert($upperCaseBase),
            ];
        }

        // if right base/quote order and web instead of mintme
        if (Symbols::WEB === $upperCaseQuote) {
            return [
                'base' => $upperCaseBase,
                'quote' => $this->rebrandingConverter->convert($upperCaseQuote),
            ];
        }

        // if reversed base/quote order but no web instead of mintme
        if (Symbols::MINTME === $upperCaseBase) {
            return [
                'base' => $upperCaseQuote,
                'quote' => $upperCaseBase,
            ];
        }

        return null;
    }
}
