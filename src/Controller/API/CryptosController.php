<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Communications\CryptoRatesFetcherInterface;
use App\Services\TranslatorService\TranslatorInterface;
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
    public function getRates(CryptoRatesFetcherInterface $cryptoRatesFetcher, TranslatorInterface $translator): View
    {
        try {
            return $this->view($cryptoRatesFetcher->fetch());
        } catch (\Throwable $e) {
            return $this->view([
                'error' => $translator->trans('toasted.error.external'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @Rest\View()
     * @Rest\Get("/wrapped/{symbol}", name="wrapped_crypto_tokens", options={"expose"=true})
     */
    public function getWrappedToken(string $symbol): View
    {
        $crypto = $this->cryptoManager->findBySymbol($symbol);

        if (!$crypto) {
            throw $this->createNotFoundException();
        }

        return $this->view($crypto->getWrappedCryptoTokens());
    }

    /**
     * @Rest\View()
     * @Rest\Get("/networks/{symbol}", name="get_crypto_networks", options={"expose"=true})
     */
    public function getCryptoNetworks(string $symbol): View
    {
        $crypto = $this->cryptoManager->findBySymbol($symbol);

        if (!$crypto) {
            throw $this->createNotFoundException();
        }

        return $this->view($this->cryptoManager->getCryptoNetworks($crypto));
    }

    /**
     * @Rest\View()
     * @Rest\Get("/info/{symbol}", name="get_crypto_info", options={"expose"=true})
     */
    public function getCryptoInfo(string $symbol): View
    {
        $crypto = $this->cryptoManager->findBySymbol($symbol);

        if (!$crypto) {
            throw $this->createNotFoundException();
        }

        return $this->view($crypto);
    }
}
