<?php declare(strict_types = 1);

namespace App\Communications;

use App\Entity\User;
use App\Exception\ApiBadRequestException;
use Symfony\Component\HttpFoundation\Request;

class CoinifyCommunicator
{
    /** @var RestRpcInterface */
    private $rpc;

    /** @var int */
    private $partnerId;

    public function __construct(
        RestRpcInterface $rpc,
        int $partnerId
    ) {
        $this->rpc = $rpc;
        $this->partnerId = $partnerId;
    }

    public function signupTrader(User $user): string
    {
        $response = $this->rpc->send(
            'signup/trader',
            Request::METHOD_POST,
            [
                'json' => [
                    'partnerId' => $this->partnerId,
                    'email' => $user->getEmail(),
                    'password' => md5(random_bytes(10)),
                    'accountType' => 'individual',
                    'generateOfflineToken' => true,
                    'profile' => [
                        'address' => [
                            'state' => 'CA',
                            'country' => 'US',
                        ],
                    ],
                ],
            ]
        );

        $response = json_decode($response, true);

        if (!isset($response['offlineToken'])) {
            throw new ApiBadRequestException();
        }

        return $response['offlineToken'];
    }

    public function getRefreshToken(User $user): string
    {
        $response = $this->rpc->send(
            'auth',
            Request::METHOD_POST,
            [
                'json' => [
                    'grant_type' => 'offline_token',
                    'offline_token' => $user->getCoinifyOfflineToken(),
                ],
            ]
        );

        $response = json_decode($response, true);

        if (!isset($response['refresh_token'])) {
            throw new ApiBadRequestException();
        }

        return $response['refresh_token'];
    }
}
