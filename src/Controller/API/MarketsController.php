<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market\MarketCapCalculator;
use App\Exchange\Market\MarketHandlerInterface;
use App\Manager\MarketStatusManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @Rest\Route("/api/markets")
 */
class MarketsController extends APIController
{
    private const OFFSET = 2;

    /**
     * @Rest\View()
     * @Rest\Get(name="markets", options={"expose"=true})
     */
    public function getMarkets(
        MarketFactoryInterface $marketManager
    ): View {

        $markets = $marketManager->createUserRelated($this->getUser());

        return $this->view($markets);
    }

    /**
     * @Rest\View()
     * @Rest\Get("/info/{page}", defaults={"page"=1}, name="markets_info", options={"expose"=true})
     * @Rest\QueryParam(name="user")
     * @Rest\QueryParam(name="deployed", default=0)
     * @Rest\QueryParam(name="sort", default="monthVolume")
     * @Rest\QueryParam(name="order", default="DESC")
     */
    public function getMarketsInfo(
        int $page,
        ParamFetcherInterface $request,
        MarketStatusManagerInterface $marketStatusManager
    ): View {
        $user = $this->getUser();
        $user = $user instanceof User && $request->get('user')
            ? $user->getId()
            : null;

        $markets = $marketStatusManager->getMarketsInfo(
            ($page - 1) * (self::OFFSET - 1),
            self::OFFSET,
            $request->get('sort'),
            $request->get('order'),
            (int)$request->get('deployed'),
            $user
        );

        return $this->view([
            'markets' => $markets['markets'] ?? $markets,
            'rows' => $markets['count'] ?? $marketStatusManager->getMarketsCount(),
            'limit' => self::OFFSET,
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
    public function getMarketCap(MarketCapCalculator $marketCapCalculator, CacheInterface $cache, string $base = Token::BTC_SYMBOL): View
    {
        $marketCap = $cache->get("marketcap_{$base}", function (ItemInterface $item) use ($marketCapCalculator, $base) {
            $item->expiresAfter(3600);

            return $marketCapCalculator->calculate($base);
        });

        return $this->view([
            'marketcap' => $marketCap,
        ]);
    }
}
