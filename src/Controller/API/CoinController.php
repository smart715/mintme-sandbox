<?php declare(strict_types = 1);

namespace App\Controller\API;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Rest\Route("/api/coin")
 */
class CoinController extends AbstractFOSRestController
{
    /**
     * @Rest\View()
     * @Rest\GET("/total-users-registered", name="get_total_users_registered", options={"expose"=true})
     */
    public function getTotalUsersRegistered(): View
    {
        $client = new Client();
        $response = $client->request('GET', 'https://www.coinimp.com/api/get-registered-users-count');

        return $this->view(json_decode($response->getBody()->getContents(), true), Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\GET("/total-wallets-and-transactions", name="get_total_wallets_and_transactions", options={"expose"=true})
     */
    public function getTotalWalletsAndTransactions(): View
    {
        $client = new Client();
        $response = $client->request('GET', 'https://www.mintme.com/explorer/views/stats.js');

        return $this->view(json_decode($response->getBody()->getContents(), true), Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/total-network_hashrate", name="get_total_network_hashrate", options={"expose"=true})
     */
    public function getTotalNetworkHashrate(): View
    {
        $client = new Client();

        $response = $client->request('POST', 'https://www.mintme.com/explorer/web3relay', [
            'json' => [
                'action' => 'hashrate',
            ],
        ]);

        return $this->view(json_decode($response->getBody()->getContents(), true), Response::HTTP_OK);
    }
}
