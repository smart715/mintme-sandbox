<?php declare(strict_types = 1);

namespace App\Controller\CoinMarketCap\API\Outbound;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;

/**
 * @Rest\Route("/cmc/api/v1/ticker")
 */
class TickerController extends AbstractFOSRestController
{
    /**
     * Get 24-hour pricing and volume summary for each market pair available on the exchange.
     *
     * @Rest\Get("/")
     * @Rest\View()
     */
    public function getTicker(): array
    {
        //TODO:

        return [];
    }
}
