<?php declare(strict_types = 1);

namespace App\SmartContract;

use App\Communications\DeployCostFetcherInterface;
use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Balance\Exception\BalanceException;
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

    public function execute(User $user, Token $token, Crypto $crypto): void
    {
        $cost = $this->costFetcher->getDeployCost($crypto->getSymbol());
        $balance = $this->balanceHandler
            ->balance($token->getOwner(), Token::getFromCrypto($crypto))->getAvailable();

        if ($cost->greaterThan($balance)) {
            throw new BalanceException('Low balance');
        }

        $token->setCrypto($crypto);
        $this->contractHandler->deploy($token);

        $this->balanceHandler->withdraw(
            $token->getOwner(),
            Token::getFromCrypto($crypto),
            $cost
        );

        $token->setPendingDeployment();
        $token->setDeployCost($cost->getAmount());
        $this->em->persist($token);
        $this->em->flush();
    }
}
