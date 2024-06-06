<?php declare(strict_types = 1);

namespace App\Communications;

use App\Entity\User;
use App\Exception\ApiBadRequestException;
use App\Services\JwtService\JwtServiceInterface;
use Symfony\Component\HttpFoundation\Request;

class CoinifyCommunicator
{
    private RestRpcInterface $rpc;
    private int $partnerId;
    private JwtServiceInterface $jwtService;

    public function __construct(
        RestRpcInterface $rpc,
        JwtServiceInterface $jwtService,
        int $partnerId
    ) {
        $this->rpc = $rpc;
        $this->jwtService = $jwtService;
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

        if (isset($response['error']) && 'trader_exists' === $response['error']) {
            return $this->getNewOfflineToken($user);
        }

        if (!isset($response['offlineToken'])) {
            throw new ApiBadRequestException();
        }

        return $response['offlineToken'];
    }

    public function getNewOfflineToken(User $user): string
    {
        $bearerToken = $this->jwtService->createToken([
            'email' => $user->getEmail(),
        ]);

        $response = $this->rpc->send(
            'users/reset-offline-token',
            Request::METHOD_POST,
            [
                'json' => [
                    'partnerId' => $this->partnerId,
                    'email' => $user->getEmail(),
                ],
                'headers' => [
                    'Authorization' => "Bearer {$bearerToken}",
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
