<?php

namespace App\Deposit;

use App\Communications\Exception\FetchException;
use App\Communications\JsonRpcInterface;
use App\Deposit\Exception\DepositCredentialsException;
use App\Deposit\Model\DepositCredentials;
use App\Entity\Token\Token;

class DepositGatewayCommunicator implements DepositGatewayCommunicatorInterface
{
    /** @var JsonRpcInterface */
    private $jsonRpc;

    private const GET_DEPOSIT_CREDENTIALS_METHOD = "get_deposit_credentials";

    public function __construct(JsonRpcInterface $jsonRpc)
    {
        $this->jsonRpc = $jsonRpc;
    }

    public function getDepositCredentials(int $userId, array $predefinedTokens): DepositCredentials
    {
        $credentials = [];
        try {
            foreach ($predefinedTokens as $token) {
                $response = $this->jsonRpc->send(
                    self::GET_DEPOSIT_CREDENTIALS_METHOD,
                    [
                        'user_id' => $userId,
                        "currency" => $token->getName(),
                    ]
                );
                $credentials[$token->getName()] = $response->hasError() ?
                "Address unavailable.":
                $response->getResult();
            }
        } catch (FetchException $e) {
            throw new DepositCredentialsException();
        }

        return new DepositCredentials($credentials);
    }

    public function getUnavailableCredentials(array $predefinedTokens): DepositCredentials
    {
        $unavailableCredentials = [];
        foreach ($predefinedTokens as $token) {
            $unavailableCredentials[$token->getName()] = 'Address unavailable.';
        }

        return new DepositCredentials($unavailableCredentials);
    }
}
