<?php declare(strict_types = 1);

namespace App\SmartContract;

use App\Communications\Exception\FetchException;
use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\Token\TokenDeploy;
use App\Entity\TradableInterface;
use App\Entity\User;
use App\SmartContract\Model\AddTokenResult;
use App\Utils\AssetType;
use App\Wallet\Model\DepositInfo;
use App\Wallet\Model\WithdrawInfo;
use Exception;
use Money\Money;

interface ContractHandlerInterface
{
    /**
     * @throws Exception
     * @param TokenDeploy $deploy
     */
    public function deploy(TokenDeploy $deploy, bool $isMainDeploy): void;

    /**
     * For Token entities or Cryptos with WrappedCryptoToken relation
     */
    public function addToken(
        TradableInterface $tradable,
        Crypto $cryptoNetwork,
        string $address,
        ?string $minDeposit,
        bool $isCrypto = false,
        bool $isPausable = false
    ): AddTokenResult;

    public function getContractMethodFee(string $cryptoSymbol): Money;

    public function updateMintDestination(Token $token, string $address): void;

    public function getDepositCredentials(User $user): array;

    public function getDepositInfo(TradableInterface $tradable, Crypto $cryptoNetwork): DepositInfo;

    /** @throws FetchException */
    public function getWithdrawInfo(Crypto $cryptoNetwork, TradableInterface $tradable): WithdrawInfo;

    public function withdraw(
        User $user,
        Money $balance,
        string $address,
        TradableInterface $token,
        Crypto $crypto,
        Money $fee
    ): void;

    public function getAllRawTransactions(User $user, int $offset, int $limit): array;

    public function getTransactions(User $user, int $offset, int $limit): array;

    public function getPendingWithdrawals(User $user, string $asset): array;

    public function ping(): bool;

    public function getDecimalsContract(string $tokenAddress, string $blockchain): int;

    public function updateTokenStatus(
        TradableInterface $tradable,
        ?Crypto $cryptoBlockchain,
        bool $status,
        bool $runDelayedTransactions
    ): void;
}
