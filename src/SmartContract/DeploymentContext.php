<?php declare(strict_types = 1);

namespace App\SmartContract;

class DeploymentContext
{
    private DeploymentStrategyInterface $deploymentStrategy;

    public function __construct(DeploymentStrategyInterface $deploymentStrategy)
    {
        $this->deploymentStrategy = $deploymentStrategy;
    }

    public function deploy(): void
    {
        $this->deploymentStrategy->deploy();
    }
}
