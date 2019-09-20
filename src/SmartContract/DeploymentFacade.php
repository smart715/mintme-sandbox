<?php declare(strict_types = 1);

namespace App\SmartContract;

use App\Communications\DeployCostFetcherInterface;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Balance\Exception\BalanceException;
use Doctrine\ORM\EntityManagerInterface;

class DeploymentFacade implements DeploymentFacadeInterface
{
    /** @var EntityManagerInterface */
    private $em;

    /** @var DeployCostFetcherInterface */
    private $costFetcher;

    /** @var BalanceHandlerInterface */
    private $balanceHandler;

    /** @var ContractHandlerInterface */
    private $contractHandler;

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
        $cost = $this->costFetcher->getDeployWebCost();
        $balance = $this->balanceHandler
            ->balance($user, Token::getFromSymbol(Token::WEB_SYMBOL))->getAvailable();

        if ($cost->greaterThan($balance)) {
            throw new BalanceException('Low balance');
        }

        $this->contractHandler->deploy($token);

        $this->balanceHandler->withdraw(
            $user,
            Token::getFromSymbol(Token::WEB_SYMBOL),
            $cost
        );

        $token->setPendingDeployment();
        $token->setDeployCost($cost->getAmount());
        $this->em->persist($token);
        $this->em->flush();
    }
}
