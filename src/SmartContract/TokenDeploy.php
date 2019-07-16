<?php

namespace App\SmartContract;

use App\Communications\JsonRpcInterface;
use App\Entity\Token\Token;
use App\SmartContract\Config\Config;
use App\Utils\Converter\TokenNameConverterInterface;
use Exception;

class TokenDeploy implements TokenDeployInterface
{
    private const DEPLOY = 'deploy';

    /** @var JsonRpcInterface */
    private $rpc;

    /** @var Config */
    private $config;

    /** @var TokenNameConverterInterface */
    private $tokenNameConverter;

    public function __construct(
        JsonRpcInterface $rpc,
        Config $config,
        TokenNameConverterInterface $tokenNameConverter
    ) {
        $this->rpc = $rpc;
        $this->config = $config;
        $this->tokenNameConverter = $tokenNameConverter;
    }

    public function deploy(Token $token): array
    {
        $response = $this->rpc->send(
            self::DEPLOY,
            [
                'name' => $token->getName(),
                'symbol' => $this->tokenNameConverter->convert($token),
                'decimals' => $this->config->getTokenPrecision(),
                'mintDestination' => $this->config->getMintmeAddress(),
                'releasedAtCreation' => $this->config->getTokenQuantity(),
                'releasePeriod' => $token->getLockIn()
                    ? $token->getLockIn()->getReleasePeriod()
                    : '0',
            ]
        );

        if ($response->hasError()) {
            throw new Exception($response->getError()['message'] ?? 'get error response');
        }

        $result = $response->getResult();

        var_dump($result);
        exit;

        return [];
    }
}