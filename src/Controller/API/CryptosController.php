<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Communications\CryptoRatesFetcherInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @Rest\Route("/api/cryptos")
 * @Security(expression="is_granted('prelaunch')")
 */
class CryptosController extends APIController
{
    /**
     * @Rest\View()
     * @Rest\Get("/rates", name="exchange_rates", options={"expose"=true})
     */
    public function getRates(CryptoRatesFetcherInterface $cryptoRatesFetcher): View
    {
        return $this->view($cryptoRatesFetcher->get());
    }
}
