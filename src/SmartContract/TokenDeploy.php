<?php declare(strict_types = 1);

namespace App\SmartContract;

use App\Communications\JsonRpcInterface;
use App\Entity\Token\Token;
use App\SmartContract\Config\Config;
use App\SmartContract\Model\TokenDeployResult;
use App\Utils\Converter\TokenNameConverterInterface;
use Exception;
use Psr\Log\LoggerInterface;

class TokenDeploy implements TokenDeployInterface
{
    private const DEPLOY = 'deploy';

    /** @var JsonRpcInterface */
    private $rpc;

    /** @var Config */
    private $config;

    /** @var TokenNameConverterInterface */
    private $tokenNameConverter;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        JsonRpcInterface $rpc,
        Config $config,
        TokenNameConverterInterface $tokenNameConverter,
        LoggerInterface $logger
    ) {
        $this->rpc = $rpc;
        $this->config = $config;
        $this->tokenNameConverter = $tokenNameConverter;
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
                'symbol' => $this->tokenNameConverter->convert($token),
                'decimals' => $this->config->getTokenPrecision(),
                'mintDestination' => $this->config->getMintmeAddress(),
                'releasedAtCreation' => bcmul(
                    $this->config->getTokenQuantity(),
                    bcpow('10', (string)$this->config->getTokenPrecision())
                ),
                'releasedPeriod' => $token->getLockIn()->getReleasePeriod(),
            ]
        );

        if ($response->hasError()) {
            $this->logger->error("Failed to deploy token '{$token->getName()}'");

            throw new Exception($response->getError()['message'] ?? 'get error response');
        }

        $result = $response->getResult();

        if (!isset($result['address']) || !isset($result['transactionHash'])) {
            $this->logger->error("Failed to deploy token '{$token->getName()}'");

            throw new Exception('get error response');
        }

        return new TokenDeployResult($result['address'], $result['transactionHash']);
    }
}
