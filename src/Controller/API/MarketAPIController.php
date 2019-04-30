<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Entity\MarketStatus;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market;
use App\Exchange\Market\MarketHandlerInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Repository\MarketStatusRepository;
use App\Utils\MarketNameParserInterface;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\View\View;
use InvalidArgumentException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @Rest\Route("/api/markets")
 * @Security(expression="is_granted('prelaunch')")
 */
class MarketAPIController extends APIController
{

    /**
     * @Rest\View()
     * @Rest\Get("/", name="markets", options={"expose"=true})
     */
    public function getMarkets(
        MarketFactoryInterface $marketManager
    ): View {

        $markets = $marketManager->createUserRelated($this->getUser());

        return $this->view($markets);
    }

    /**
     * @Rest\View()
     * @Rest\Get("/info", name="markets_info", options={"expose"=true})
     */
    public function getMarketsInfo(
        MarketFactoryInterface $marketManager,
        MarketHandlerInterface $marketHandler
    ): View {
        return $this->view(
            $marketHandler->getMarketsInfo($marketManager->createAll())
        );
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
     * @Rest\Get("/update/{base}/{quote}", name="update_market_status", options={"expose"=true})
     */
    public function updateMarketStatus(
        string $base,
        string $quote,
        EntityManagerInterface $em,
        MarketHandlerInterface $marketHandler
    ): View {
        $market = $this->getMarket($base, $quote);

        if (!$market) {
            throw new InvalidArgumentException();
        }

        $marketInfo = $marketHandler->getMarketInfo($market);

        /** @var MarketStatusRepository $marketRep */
        $marketRep = $em->getRepository(MarketStatus::class);
        $marketStatus = $marketRep->findByName($quote);

        if (!$marketStatus) {
            throw new InvalidArgumentException();
        }

        $marketStatus->updateStats($marketInfo);

        $em->merge($marketStatus);
        $em->flush();

        return $this->view(true);
    }
}
