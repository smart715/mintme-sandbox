<?php declare(strict_types = 1);

namespace App\Controller;

use App\Config\TradingConfig;
use App\Exchange\Config\MarketPairsConfig;
use App\Manager\CryptoManagerInterface;
use App\Manager\MarketStatusManager;
use App\Manager\MarketStatusManagerInterface;
use App\Manager\TokenPromotionManagerInterface;
use App\Security\Config\DisabledServicesConfig;
use App\Utils\Symbols;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class TradingController extends Controller
{
    private const TRADING_COINS_TYPE = "coins";

    private MarketStatusManagerInterface $marketStatusManager;
    private MarketPairsConfig $marketPairsConfig;
    private DisabledServicesConfig $disabledServicesConfig;
    private TokenPromotionManagerInterface $tokenPromotionManager;

    public function __construct(
        NormalizerInterface $normalizer,
        MarketStatusManagerInterface $marketStatusManager,
        MarketPairsConfig $marketPairsConfig,
        DisabledServicesConfig $disabledServicesConfig,
        TokenPromotionManagerInterface $tokenPromotionManager
    ) {
        parent::__construct($normalizer);

        $this->marketStatusManager = $marketStatusManager;
        $this->marketPairsConfig = $marketPairsConfig;
        $this->disabledServicesConfig = $disabledServicesConfig;
        $this->tokenPromotionManager = $tokenPromotionManager;
    }

    /**
     * @Route("/trade/{type}",
     *     requirements={"type"="(coins|tokens)"},
     *     name="trading",
     *     defaults={"type" = null},
     *     options={"expose"=true,
     *          "sitemap" = true,
     *          "2fa_progress"=false
     *     }
     * )
     */
    public function trading(
        ?string $type,
        CryptoManagerInterface $cryptoManager,
        TradingConfig $tradingConfig,
        Request $request
    ): Response {
        if (null === $type) {
            return $this->redirectToRoute('trading', ['type' => 'coins']);
        }

        $isCoinsTrading = self::TRADING_COINS_TYPE === $type;

        $sort = $isCoinsTrading
            ? MarketStatusManager::SORT_MONTH_VOLUME
            : MarketStatusManager::SORT_RANK;
        $order = $isCoinsTrading
            ? 'DESC'
            : 'ASC';
        $page = $this->getPage($request);
        $filters = [MarketStatusManager::buildDeployedOnlyFilter(Symbols::WEB)];
        $crypto = Symbols::WEB;

        $marketsOnFirstPage = $tradingConfig->getMarketsOnFirstPage();
        $offset = ($page - 1) * $marketsOnFirstPage;

        $lastPage = true;

        if ($isCoinsTrading) {
            $markets = $this->marketStatusManager->getPredefinedMarketStatuses();
        } else {
            $markets = $this->marketStatusManager->getFilteredMarketStatuses(
                $offset,
                $marketsOnFirstPage + 1,
                $sort,
                $order,
                $filters,
                null,
                $crypto
            );

            if (count($markets) === $marketsOnFirstPage + 1) {
                array_pop($markets);

                $lastPage = false;
            }

            $promotedMarkets = $this->marketStatusManager->getFilteredPromotedMarketStatuses();

            foreach ($promotedMarkets as $name => $market) {
                $market = $this->normalize($market, ['Default', 'API']);
                $promotedMarkets[$name] = $market;
            }
        }

        foreach ($markets as $name => $market) {
            $market = $this->normalize($market, ['Default', 'API']);
            $markets[$name] = $market;
        }

        $totalPages = $this->getTotalPages(
            $marketsOnFirstPage,
            $filters[0],
            $crypto
        );

        $cryptos = $cryptoManager->findAllIndexed('symbol');

        $allDeployBlockchains = array_values(array_filter(
            $this->disabledServicesConfig->getAllDeployBlockchains(),
            static function ($symbol) use ($cryptos) {
                return (bool)($cryptos[$symbol] ?? false);
            }
        ));

        $tokenPromotions = $this->tokenPromotionManager->findActivePromotions();

        return $this->render('pages/trading.html.twig', [
            'cryptos' => $this->normalize(array_values($cryptos)),
            'tokensCount' => $this->marketStatusManager->getMarketsCount(
                MarketStatusManager::FILTER_DEPLOYED_TOKEN
            ),
            'totalPages' => $totalPages,
            'sort' => $sort,
            'order' => $order,
            'filterForTokens'=> $this->marketStatusManager->getFilterForTokens(),
            'cryptoTopListMarketKeys' => $this->marketPairsConfig->getJoinedTopListPairs(),
            'markets' => $markets['markets'] ?? $markets,
            'promotedMarkets' => empty($promotedMarkets) ? new \stdClass() : $promotedMarkets,
            'page' => $page,
            'lastPage' => $lastPage,
            'tokensOnPage' => $marketsOnFirstPage,
            'type' => $type,
            'allDeployBlockchains' => $allDeployBlockchains,
            'tokenPromotions' => $this->normalize($tokenPromotions, ['API_BASIC']),
        ]);
    }

    private function getPage(Request $request): int
    {
        $page = $request->query->get('page');

        return is_numeric($page) && (int)$page > 0
            ? (int)$page
            : 1;
    }

    private function getTotalPages(
        int $marketsOnFirstPage,
        string $filter,
        string $crypto
    ): int {
        $totalCount = $this->marketStatusManager->getMarketsCount($filter, $crypto);

        return (int)ceil($totalCount / $marketsOnFirstPage);
    }
}
