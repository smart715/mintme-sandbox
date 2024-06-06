<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Communications\GeckoCoin\GeckoCoinCommunicatorInterface;
use App\Communications\MarketCostFetcherInterface;
use App\Config\HideFeaturesConfig;
use App\Config\TradingConfig;
use App\Entity\User;
use App\Exception\ApiNotFoundException;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market;
use App\Exchange\Market\MarketCapCalculator;
use App\Exchange\Market\MarketHandler;
use App\Exchange\Market\MarketHandlerInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\MarketStatusManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Services\TranslatorService\TranslatorInterface;
use App\Utils\Symbols;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @Rest\Route("/api/markets")
 */
class MarketsController extends APIController
{
    private TranslatorInterface $translator;
    private LoggerInterface $logger;

    public function __construct(
        CryptoManagerInterface $cryptoManager,
        TokenManagerInterface $tokenManager,
        MarketFactoryInterface $marketFactory,
        TranslatorInterface $translator,
        LoggerInterface $logger
    ) {
        parent::__construct($cryptoManager, $tokenManager, $marketFactory);
        $this->translator = $translator;
        $this->logger = $logger;
    }

    /**
     * @Rest\View()
     * @Rest\Get(name="markets", options={"expose"=true})
     */
    public function getMarkets(
        MarketFactoryInterface $marketManager
    ): View {

        $currentUser = $this->getUser();

        if (!$currentUser || !$currentUser instanceof User) {
            throw new AccessDeniedHttpException();
        }

        $markets = $marketManager->createUserRelated($currentUser);

        return $this->view($markets);
    }

    /**
     * @Rest\View()
     * @Rest\Get("/info/{page}", defaults={"page"=1}, name="markets_info", options={"expose"=true})
     * @Rest\QueryParam(name="sort", default="rank")
     * @Rest\QueryParam(name="order", default="DESC")
     * @Rest\QueryParam(name="filters", nullable=true, default=null)
     * @Rest\QueryParam(name="crypto", nullable=true, default=null)
     * @Rest\QueryParam(name="searchPhrase", nullable=true, default=null)
     * @Rest\QueryParam(name="type", requirements="coins|tokens", default="tokens")
     */
    public function getMarketsInfo(
        int $page,
        ParamFetcherInterface $request,
        MarketStatusManagerInterface $marketStatusManager,
        TradingConfig $tradingConfig
    ): View {
        $user = $this->getUser();
        $user = $user instanceof User
            ? $user->getId()
            : null;

        $filters = $request->get('filters') ?? [];
        $crypto = $request->get('crypto') ?? Symbols::WEB;
        $searchPhrase = (string)$request->get('searchPhrase');
        $sort = (string)$request->get('sort');
        $order = (string)$request->get('order');
        $type = (string)$request->get('type');

        $marketsOnFirstPage = $tradingConfig->getMarketsOnFirstPage();
        $marketsPerPage = $tradingConfig->getMarketsPerPage();

        $offset = 1 === $page
            ? 0
            : ($marketsOnFirstPage - $marketsPerPage) + $marketsPerPage * ($page - 1);

        $limit = 1 === $page
            ? $marketsOnFirstPage
            : $marketsPerPage;

        $lastPage = true;

        if ('coins' === $type) {
            $markets = $marketStatusManager->getPredefinedMarketStatuses();
        } else {
            $markets = $marketStatusManager->getFilteredMarketStatuses(
                $offset,
                $limit + 1,
                $sort,
                $order,
                $filters,
                $user,
                $crypto,
                $searchPhrase
            );

            if (count($markets) === $limit + 1) {
                array_pop($markets);

                $lastPage = false;
            }
        }

        return $this->view([
            'markets' => $markets['markets'] ?? $markets,
            'lastPage' => $lastPage,
        ]);
    }

    /**
     * @Rest\View()
     * @Rest\Get("/{base}/{quote}/kline", name="market_kline", options={"expose"=true})
     */
    public function getMarketKline(
        string $base,
        string $quote,
        MarketHandlerInterface $marketHandler,
        Request $request
    ): View {
        $market = $this->getMarket($base, $quote);

        if (!$market) {
            throw new ApiNotFoundException();
        }

        $periods = [
            MarketHandler::PERIOD_TYPE_WEEK,
            MarketHandler::PERIOD_TYPE_MONTH,
            MarketHandler::PERIOD_TYPE_HALF_YEAR,
        ];

        if ($request->get('period') && in_array($request->get('period'), $periods)) {
            return $this->view(
                $marketHandler->getKLineStatByPeriod($market, $request->get('period'))
            );
        }

        return $this->view(
            $marketHandler->getKLineStatDaily($market)
        );
    }

    /**
     * @Rest\View()
     * @Rest\Get("/marketcap/{base}", name="marketcap", options={"expose"=true})
     */
    public function getMarketCap(
        MarketCapCalculator $marketCapCalculator,
        CacheInterface $cache,
        string $base = Symbols::BTC
    ): View {
        $marketCap = $cache->get("marketcap_{$base}", function (ItemInterface $item) use ($marketCapCalculator, $base) {
            $item->expiresAfter(3600);

            return $marketCapCalculator->calculate($base);
        });

        return $this->view([
            'marketcap' => $marketCap,
        ]);
    }

    /**
     * @Rest\View()
     * @Rest\Get("/{quote}/status", name="markets_status", options={"expose"=true})
     */
    public function getMarketsStatus(
        string $quote,
        MarketFactoryInterface $marketFactory,
        MarketHandlerInterface $marketHandler,
        TokenManagerInterface $tokenManager
    ): View {
        $quote = $tokenManager->findByName($quote);

        if (!$quote) {
            throw new \InvalidArgumentException("Token not found: $quote");
        }

        $markets = $marketFactory->createTokenMarkets($quote);

        if (!$markets) {
            throw new ApiNotFoundException('Market does not exist');
        }

        return $this->view(
            array_map(function (Market $market) use ($marketHandler) {
                return $marketHandler->getMarketStatus($market);
            }, $markets)
        );
    }

    /**
     * @Rest\View()
     * @Rest\Get(
     *     "/costs",
     *     name="markets_costs",
     *     options={"expose"=true},
     *     condition="%feature_create_new_markets_enabled%"
     * )
     */
    public function getMarketsCosts(MarketCostFetcherInterface $costFetcher): View
    {
        $user = $this->getUser();

        if (!$user) {
            throw new AccessDeniedHttpException();
        }

        try {
            return $this->view($costFetcher->getCosts());
        } catch (\Throwable $e) {
            $this->logger->error('Error fetching markets costs: ' . $e->getMessage());

            return $this->view([
                'error' => $this->translator->trans('toasted.error.external'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @Rest\View()
     * @Rest\Get(
     *     "/circulating-supply/{symbol}",
     *     name="markets_circulating_supply",
     *     options={"expose"=true},
     * )
     */
    public function getCirculatingSupply(
        string $symbol,
        GeckoCoinCommunicatorInterface $geckoCoinCommunicator,
        CacheInterface $cache,
        HideFeaturesConfig $hideFeaturesConfig
    ): View {
        if (!$hideFeaturesConfig->isCryptoEnabled($symbol)) {
            return $this->view([], Response::HTTP_BAD_REQUEST);
        }

        try {
            $circulatingSupply = $cache->get(
                "circulating_supply_{$symbol}",
                function (ItemInterface $item) use ($symbol, $geckoCoinCommunicator, $cache) {
                    $item->expiresAfter(3600);

                    return $geckoCoinCommunicator->fetchCryptoCirculatingSupply($symbol, $cache);
                }
            );

            return $this->view(['circulatingSupply' => $circulatingSupply]);
        } catch (\Throwable $e) {
            $this->logger->error('Error fetching circulating supply: ' . $e->getMessage());

            return $this->view([], Response::HTTP_NOT_FOUND);
        }
    }
}
