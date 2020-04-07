<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market;
use App\Exchange\Market\MarketCapCalculator;
use App\Exchange\Market\MarketHandlerInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\MarketStatusManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Utils\MarketNameParserInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use InvalidArgumentException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @Rest\Route("/api/markets")
 * @Security(expression="is_granted('prelaunch')")
 */
class MarketsController extends APIController
{
    private const OFFSET = 50;

    /**
     * @Rest\View()
     * @Rest\Get(name="markets", options={"expose"=true})
     */
    public function getMarkets(
        MarketFactoryInterface $marketManager
    ): View {

        $currentUser = $this->getUser();
        $markets = null;

        if ($currentUser instanceof User) {
            $markets = $marketManager->createUserRelated($currentUser);
        }

        return $this->view($markets);
    }

    /**
     * @Rest\View()
     * @Rest\Get("/info/{page}", defaults={"page"=1}, name="markets_info", options={"expose"=true})
     * @Rest\QueryParam(name="user")
     * @Rest\QueryParam(name="deployed")
     */
    public function getMarketsInfo(
        int $page,
        ParamFetcherInterface $request,
        MarketStatusManagerInterface $marketStatusManager
    ): View {
        $deployed = !!$request->get('deployed');

        /** @var User $user */
        $user = $this->getUser();

        $markets = $request->get('user') || $deployed
            ? $marketStatusManager->getUserMarketStatus(
                $user,
                ($page - 1) * self::OFFSET,
                self::OFFSET,
                $deployed
            )
            : $marketStatusManager->getMarketsInfo(($page - 1) * self::OFFSET, self::OFFSET);

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
