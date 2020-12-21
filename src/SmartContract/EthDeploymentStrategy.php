<?php declare(strict_types = 1);

namespace App\SmartContract;

use App\Entity\Token\Token;
use Doctrine\ORM\EntityManagerInterface;

class EthDeploymentStrategy implements DeploymentStrategyInterface
{
    private Token $token;
    private ContractHandlerInterface $contractHandler;
    private EntityManagerInterface $em;


    public function __construct(
        Token $token,
        ContractHandlerInterface $contractHandler,
        EntityManagerInterface $em
    ) {
        $this->token = $token;
        $this->contractHandler = $contractHandler;
        $this->em = $em;
    }

    public function deploy(): void
    {
        $this->contractHandler->deploy($this->token);

        $this->token->setPendingDeployment();
        $this->token->setDeployCost('0');
        $this->em->persist($this->token);
        $this->em->flush();
    }
}
