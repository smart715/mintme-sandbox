<?php declare(strict_types = 1);

namespace App\SmartContract;

use App\Entity\Token\Token;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Balance\Exception\BalanceException;
use Doctrine\ORM\EntityManagerInterface;
use Money\Money;

class MintmeDeploymentStrategy implements DeploymentStrategyInterface
{
    private Token $token;
    private ContractHandlerInterface $contractHandler;
    private EntityManagerInterface $em;
    private BalanceHandlerInterface $balanceHandler;
    private Money $cost;

    public function __construct(
        Token $token,
        ContractHandlerInterface $contractHandler,
        EntityManagerInterface $em,
        BalanceHandlerInterface $balanceHandler,
        Money $cost
    ) {
        $this->token = $token;
        $this->contractHandler = $contractHandler;
        $this->em = $em;
        $this->balanceHandler = $balanceHandler;
        $this->cost = $cost;
    }

    public function deploy(): void
    {
        $balance = $this->balanceHandler
            ->balance($this->token->getOwner(), Token::getFromSymbol($this->token->getCryptoSymbol()))->getAvailable();

        if ($this->cost->greaterThan($balance)) {
            throw new BalanceException('Low balance');
        }

        $this->contractHandler->deploy($this->token);

        $this->balanceHandler->withdraw(
            $this->token->getOwner(),
            Token::getFromSymbol($this->token->getCryptoSymbol()),
            $this->cost
        );

        $this->token->setPendingDeployment();
        $this->token->setDeployCost($this->cost->getAmount());
        $this->em->persist($this->token);
        $this->em->flush();
    }
}
