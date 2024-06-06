<?php declare(strict_types = 1);

namespace App\Entity\InternalTransaction;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TradableInterface;
use App\Entity\User;
use App\Wallet\Model\Address;
use App\Wallet\Model\Amount;
use Doctrine\ORM\Mapping as ORM;
use Money\Currency;
use Money\Money;

/**
 * @codeCoverageIgnore
 * @ORM\Entity()
 */
class TokenInternalTransaction extends InternalTransaction
{
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Token\Token")
     * @ORM\JoinColumn(name="token_id", referencedColumnName="id")
     */
    private Token $token;

    public function __construct(
        User $user,
        Token $token,
        Crypto $cryptoNetwork,
        Amount $amount,
        Address $address,
        Money $fee,
        string $type
    ) {
        parent::__construct($user, $cryptoNetwork, $amount, $address, $fee, $type);

        $this->token = $token;
    }

    public function getToken(): Token
    {
        return $this->token;
    }

    public function getAmount(): Amount
    {
        return new Amount(
            new Money(
                $this->amount,
                new Currency($this->token->getSymbol())
            )
        );
    }

    public function getSymbol(): string
    {
        return $this->getToken()->getSymbol();
    }

    public function getTradable(): TradableInterface
    {
        return $this->getToken();
    }
}
