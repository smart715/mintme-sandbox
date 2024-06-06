<?php declare(strict_types = 1);

namespace App\Exchange\Balance\Strategy;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TradableInterface;
use App\Entity\User;
use App\Exception\InvalidTokenDeploy;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Manager\CryptoManagerInterface;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use App\Wallet\WalletInterface;

class DepositTokenStrategy implements BalanceStrategyInterface
{
    /** @var BalanceHandlerInterface */
    private $balanceHandler;

    /** @var WalletInterface */
    private $wallet;

    /** @var MoneyWrapperInterface */
    private $moneyWrapper;

    private CryptoManagerInterface $cryptoManager;

    private Crypto $cryptoDeploy;

    public function __construct(
        BalanceHandlerInterface $balanceHandler,
        WalletInterface $wallet,
        MoneyWrapperInterface $moneyWrapper,
        CryptoManagerInterface $cryptoManager,
        Crypto $cryptoDeploy
    ) {
        $this->balanceHandler = $balanceHandler;
        $this->wallet = $wallet;
        $this->moneyWrapper = $moneyWrapper;
        $this->cryptoManager = $cryptoManager;
        $this->cryptoDeploy = $cryptoDeploy;
    }

    /** @param Token $tradable */
    public function deposit(User $user, TradableInterface $tradable, string $amount): void
    {
        if (!$tradable->getDeployByCrypto($this->cryptoDeploy)) {
            throw new InvalidTokenDeploy();
        }

        $this->withdrawBaseFee($user, $tradable);
        $this->depositTokens($user, $tradable, $amount);
    }

    private function depositTokens(User $user, Token $token, string $amount): void
    {
        $this->balanceHandler->deposit(
            $user,
            $token,
            $this->moneyWrapper->parse($amount, Symbols::TOK)
        );
    }

    private function withdrawBaseFee(User $user, Token $token): void
    {
        $tokenDeposit = $this->wallet->getDepositInfo($token, $this->cryptoDeploy);

        if (!$tokenDeposit) {
            return;
        }

        $tokenDepositFee = $tokenDeposit->getFee();

        if ($tokenDepositFee->isNegative() || $tokenDepositFee->isZero()) {
            return;
        }

        $this->balanceHandler->withdraw(
            $user,
            $this->cryptoManager->findBySymbol($token->getCryptoSymbol()),
            $tokenDepositFee
        );
    }
}
