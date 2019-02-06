<?php

namespace App\Controller\API;

use App\Manager\MarketManagerInterface;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;


/**
 * @Rest\Route("/api/markets")
 * @Security(expression="is_granted('prelaunch')")
 */
class MarketAPIController extends FOSRestController
{

    /**
     * @Rest\View()
     * @Rest\GET("/", name="markets", options={"expose"=true})
     */
    public function getMarkets(
        MarketManagerInterface $marketManager,
        NormalizerInterface $normalizer
    ): View {

        $markets = $normalizer->normalize($marketManager->getUserRelatedMarkets($this->getUser()), null, [
            'groups' => ['Default'],
        ]);

        return $this->view($markets);
    }

}