<?php declare(strict_types = 1);

namespace App\SmartContract;

interface DeploymentStrategyInterface
{
    public function deploy(): void;
}
