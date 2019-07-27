<?php declare(strict_types = 1);

namespace App\SmartContract;

use App\Communications\JsonRpcInterface;
use App\Entity\Token\Token;
use App\Entity\User;
use App\SmartContract\Config\Config;
use App\SmartContract\Model\TokenDeployResult;
use App\Wallet\Deposit\Model\DepositCredentials;
use App\Wallet\Model\Address;
use Exception;
use Psr\Log\LoggerInterface;

class ContractHandler implements ContractHandlerInterface
{
    private const DEPLOY = 'deploy';
    private const UPDATE_MIN_DESTINATION = 'update_mint_destination';
    private const DEPOSIT_CREDENTIAL = 'get_deposit_credential';

    /** @var JsonRpcInterface */
    private $rpc;

    /** @var Config */
    private $config;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        JsonRpcInterface $rpc,
        Config $config,
        LoggerInterface $logger
    ) {
        $this->rpc = $rpc;
        $this->config = $config;
        $this->logger = $logger;
    }

    public function deploy(Token $token): TokenDeployResult
    {
        if (!$token->getLockIn()) {
            $this->logger->error("Failed to deploy token '{$token->getName()}' because It has not a release period");

            throw new Exception('Token dose not has release period');
        }

        $response = $this->rpc->send(
            self::DEPLOY,
            [
                'name' => $token->getName(),
                'decimals' => $this->config->getTokenPrecision(),
                'mintDestination' => $this->config->getMintmeAddress(),
                'releasedAtCreation' => bcmul(
                    $this->config->getTokenQuantity(),
                    bcpow('10', (string)$this->config->getTokenPrecision())
                ),
                'releasePeriod' => $token->getLockIn()->getReleasePeriod(),
            ]
        );

        if ($response->hasError()) {
            $this->logger->error("Failed to deploy token '{$token->getName()}'");

            throw new Exception($response->getError()['message'] ?? 'get error response');
        }

        $result = $response->getResult();

        if (!isset($result['address'])) {
            $this->logger->error("Failed to deploy token '{$token->getName()}'");

            throw new Exception('get error response');
        }

        return new TokenDeployResult($result['address']);
    }

    public function updateMinDestination(Token $token, string $address, bool $lock): void
    {
        if ($token->isMinDestinationLocked()) {
            $this->logger->error("Failed to Update minDestination for '{$token->getName()}' because It is locked");

            throw new Exception('Token dose not has release period');
        }

        $response = $this->rpc->send(
            self::UPDATE_MIN_DESTINATION,
            [
                'tokenContract' => $token->getAddress(),
                'mintDestination' => $address,
                'lock'=> $lock,
            ]
        );

        if ($response->hasError()) {
            $this->logger->error("Failed to update minDestination for '{$token->getName()}'");

            throw new Exception($response->getError()['message'] ?? 'get error response');
        }
    }

    public function getDepositCredentials(User $user): DepositCredentials
    {
        $credentials = [];

        foreach ($user->getRelatedTokens() as $token) {
            $response = $this->rpc->send(
                self::DEPOSIT_CREDENTIAL,
                [
                    'userId' => $user->getId(),
                    'tokenName' => $token->getName(),
                ]
            );
            $credentials[$token->getName()] = $response->hasError()
                ? "Address unavailable."
                : $response->getResult();
        }

        return new DepositCredentials($credentials);
    }
}
