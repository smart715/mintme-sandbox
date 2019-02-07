<?php

namespace App\Controller\API;

use App\Exchange\Market\MarketHandlerInterface;
use App\Manager\MarketManagerInterface;
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
        MarketManagerInterface $marketManager
    ): View {

        $markets = $marketManager->getUserRelatedMarkets($this->getUser());

        return $this->view($markets);
    }

    /**
     * @Rest\View()
     * @Rest\Get("/info", name="markets_info", options={"expose"=true})
     */
    public function getMarketsInfo(
        MarketManagerInterface $marketManager,
        MarketHandlerInterface $marketHandler
    ): View {
        $marketsInfo = $marketHandler->getMarketsInfo($marketManager->getAllMarkets());

        return $this->view($marketsInfo);
    }
}
