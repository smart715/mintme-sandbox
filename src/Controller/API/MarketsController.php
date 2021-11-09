<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Entity\User;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market\MarketCapCalculator;
use App\Exchange\Market\MarketHandlerInterface;
use App\Manager\MarketStatusManagerInterface;
use App\Utils\Symbols;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @Rest\Route("/api/markets")
 */
class MarketsController extends APIController
{
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
     * @Rest\QueryParam(name="user")
     * @Rest\QueryParam(name="filter", default=0)
     * @Rest\QueryParam(name="sort", default="rank")
     * @Rest\QueryParam(name="order", default="DESC")
     */
    public function getMarketsInfo(
        int $page,
        ParamFetcherInterface $request,
        MarketStatusManagerInterface $marketStatusManager
    ): View {
        /** @var User $user */
        $user = $this->getUser();
        $user = $user instanceof User && $request->get('user')
            ? $user->getId()
            : null;

        $filter = (int)$request->get('filter');
        $tokensOnPage = (int)$this->getParameter('tokens_on_page');

        $markets = $marketStatusManager->getMarketsInfo(
            $tokensOnPage * ($page - 1),
            $tokensOnPage,
            $request->get('sort'),
            $request->get('order'),
            $filter,
            $user
        );

        return $this->view([
            'markets' => $markets['markets'] ?? $markets,
            'rows' => $user
                ? $marketStatusManager->getUserRelatedMarketsCount($user)
                : $marketStatusManager->getMarketsCount($filter),
        ]);
    }

    /**
     * @Rest\View()
     * @Rest\Get("/{base}/{quote}/kline", name="market_kline", options={"expose"=true})
     */
    public function getMarketKline(
        string $base,
        string $quote,
        MarketHandlerInterface $marketHandler
    ): View {
        $market = $this->getMarket($base, $quote);

        if (!$market) {
            throw new InvalidArgumentException();
        }

        return $this->view(
            $marketHandler->getKLineStatDaily($market)
        );
    }

    /**
     * @Rest\View()
     * @Rest\Get("/marketcap/{base}", name="marketcap", options={"expose"=true})
     */
    public function getMarketCap(MarketCapCalculator $marketCapCalculator, CacheInterface $cache, string $base = Symbols::BTC): View
    {
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
     * @Rest\Get("/{base}/{quote}/status", name="market_status", options={"expose"=true})
     */
    public function getMarketStatus(
        string $base,
        string $quote,
        MarketHandlerInterface $marketHandler
    ): View {
        $market = $this->getMarket($base, $quote);

        if (!$market) {
            throw new InvalidArgumentException();
        }

        return $this->view(
            $marketHandler->getMarketStatus($market)
        );
    }
}
