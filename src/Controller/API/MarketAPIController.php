<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market\MarketHandlerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\View\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @Rest\Route("/api/markets")
 * @Security(expression="is_granted('prelaunch')")
 */
class MarketAPIController extends FOSRestController
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
        $marketsInfo = $marketHandler->getMarketsInfo($marketManager->createAll());

        return $this->view($marketsInfo);
    }
}
