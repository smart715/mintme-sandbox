<?php declare(strict_types = 1);

namespace App\SmartContract;

use App\Communications\ConnectCostFetcherInterface;
use App\Communications\DeployCostFetcherInterface;
use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\Token\TokenDeploy;
use App\Entity\TradableInterface;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Balance\BalanceTransactionBonusType;
use App\Exchange\Balance\Exception\BalanceException;
use Doctrine\ORM\EntityManagerInterface;

class DeploymentFacade implements DeploymentFacadeInterface
{
    private EntityManagerInterface $em;
    private DeployCostFetcherInterface $deployCostFetcher;
    private ConnectCostFetcherInterface $connectCostFetcher;
    private BalanceHandlerInterface $balanceHandler;
    private ContractHandlerInterface $contractHandler;

    public function __construct(
        EntityManagerInterface $em,
        DeployCostFetcherInterface $deployCostFetcher,
        ConnectCostFetcherInterface $connectCostFetcher,
        BalanceHandlerInterface $balanceHandler,
        ContractHandlerInterface $contractHandler
    ) {
        $this->em = $em;
        $this->deployCostFetcher = $deployCostFetcher;
        $this->connectCostFetcher = $connectCostFetcher;
        $this->balanceHandler = $balanceHandler;
        $this->contractHandler = $contractHandler;
    }

    /**
     * @throws \App\Communications\Exception\FetchException
     * @throws \Throwable
     * @throws BalanceException
     */
    public function execute(User $user, Token $token, Crypto $crypto): void
    {
        $isMainDeploy = !$token->getMainDeploy();

        $cost = $isMainDeploy
            ? $this->deployCostFetcher->getCost($crypto->getSymbol())
            : $this->connectCostFetcher->getCost($crypto->getSymbol());

        $balance = $this->balanceHandler
            ->balance($token->getOwner(), $crypto->getNativeCoin())->getFullAvailable();

        if ($cost->greaterThan($balance)) {
            throw new BalanceException('Low balance');
        }

        $deploy = (new TokenDeploy())
            ->setToken($token)
            ->setCrypto($crypto)
            ->setDeployCost($cost->getAmount());

        $this->contractHandler->deploy($deploy, $isMainDeploy);

        $token->addDeploy($deploy);

        if (!$cost->isZero()) {
            try {
                $this->balanceHandler->beginTransaction();

                $this->balanceHandler->withdrawBonus(
                    $token->getOwner(),
                    $crypto->getNativeCoin(),
                    $cost,
                    BalanceTransactionBonusType::DEPLOY_TOKEN
                );
            } catch (\Throwable $e) {
                $this->balanceHandler->rollback();

                throw $e;
            }
        }

        $this->em->persist($token);
        $this->em->flush();
    }
}
