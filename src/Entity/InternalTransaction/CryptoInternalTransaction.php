<?php declare(strict_types = 1);

namespace App\Entity\InternalTransaction;

use App\Entity\Crypto;
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
class CryptoInternalTransaction extends InternalTransaction
{
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Crypto")
     * @ORM\JoinColumn(name="crypto_id", referencedColumnName="id")
     */
    private Crypto $crypto;

    public function __construct(
        User $user,
        Crypto $crypto,
        Crypto $cryptoNetwork,
        Amount $amount,
        Address $address,
        Money $fee,
        string $type
    ) {
        parent::__construct($user, $cryptoNetwork, $amount, $address, $fee, $type);

        $this->crypto = $crypto;
    }

    public function getCrypto(): Crypto
    {
        return $this->crypto;
    }

    public function setCrypto(Crypto $crypto): self
    {
        $this->crypto = $crypto;

        return $this;
    }

    public function getAmount(): Amount
    {
        return new Amount(
            new Money(
                $this->amount,
                new Currency($this->crypto->getSymbol())
            )
        );
    }

    public function setAmount(Money $amount): self
    {
        $this->amount = $amount->getAmount();

        return $this;
    }

    public function getSymbol(): string
    {
        return $this->getCrypto()->getSymbol();
    }

    public function getTradable(): TradableInterface
    {
        return $this->getCrypto();
    }
}
