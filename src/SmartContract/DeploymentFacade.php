<?php declare(strict_types = 1);

namespace App\SmartContract;

use App\Communications\DeployCostFetcherInterface;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use Doctrine\ORM\EntityManagerInterface;

class DeploymentFacade implements DeploymentFacadeInterface
{
    private EntityManagerInterface $em;
    private DeployCostFetcherInterface $costFetcher;
    private BalanceHandlerInterface $balanceHandler;
    private ContractHandlerInterface $contractHandler;

    public function __construct(
        EntityManagerInterface $em,
        DeployCostFetcherInterface $costFetcher,
        BalanceHandlerInterface $balanceHandler,
        ContractHandlerInterface $contractHandler
    ) {
        $this->em = $em;
        $this->costFetcher = $costFetcher;
        $this->balanceHandler = $balanceHandler;
        $this->contractHandler = $contractHandler;
    }

    public function execute(User $user, Token $token): void
    {
        $deploymentContext = new DeploymentContext(
            $token->isMintmeToken()
                ? new MintmeDeploymentStrategy(
                    $token,
                    $this->contractHandler,
                    $this->em,
                    $this->balanceHandler,
                    $this->costFetcher->getDeployWebCost()
                )
                : new EthDeploymentStrategy($token, $this->contractHandler, $this->em)
        );

        $deploymentContext->deploy();
    }
}
