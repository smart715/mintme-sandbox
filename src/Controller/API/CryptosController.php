<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Communications\CryptoRatesFetcherInterface;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Rest\Route("/api/cryptos")
 */
class CryptosController extends APIController
{
    /**
     * @Rest\View()
     * @Rest\Get("/rates", name="exchange_rates", options={"expose"=true})
     */
    public function getRates(CryptoRatesFetcherInterface $cryptoRatesFetcher): View
    {
        try {
            return $this->view($cryptoRatesFetcher->fetch());
        } catch (\Throwable $e) {
            return $this->view([
                'error' => 'Rates could not be fetched.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
