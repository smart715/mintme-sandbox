<?php declare(strict_types = 1);

namespace App\SmartContract;

use App\Entity\Token\Token;
use App\SmartContract\Model\TokenDeployResult;
use Exception;

interface TokenDeployInterface
{
    /**
     * @throws Exception
     * @param Token $token
     * @return TokenDeployResult
     */
    public function deploy(Token $token): TokenDeployResult;
}
