<?php

namespace App\SmartContract;

use App\Entity\Token\Token;

interface TokenDeployInterface
{
    public function deploy(Token $token): array;
}