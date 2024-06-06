<?php declare(strict_types = 1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Money\Currency;
use Money\Money;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Repository\WrappedCryptoTokenRepository")
 * @ORM\Table(
 *     name="wrapped_crypto_token",
 *     indexes={
 *         @ORM\Index(name="idx_d80b6ddae9571a63", columns={"crypto_id"}),
 *         @ORM\Index(name="idx_d80b6dda9ed55ef9", columns={"crypto_deploy_id"})
 *    }
 * )
 * @codeCoverageIgnore
 */
class WrappedCryptoToken
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\ManyToOne(targetEntity=Crypto::class, inversedBy="wrappedCryptoTokens")
     * @ORM\JoinColumn(nullable=false)
     */
    private Crypto $crypto;

    /**
     * @ORM\ManyToOne(targetEntity=Crypto::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private Crypto $cryptoDeploy;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $address;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $fee;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $feeCurrency;

    /**
     * @ORM\Column(type="boolean", nullable=false, options={"default"=true})
     */
    private bool $enabled = true; // phpcs:ignore

    public function __construct(Crypto $crypto, Crypto $cryptoDeploy, ?string $address, Money $fee)
    {
        $this->crypto = $crypto;
        $this->cryptoDeploy = $cryptoDeploy;
        $this->address = $address;
        $this->fee = $fee->getAmount();
        $this->feeCurrency = $fee->getCurrency()->getCode();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCrypto(): Crypto
    {
        return $this->crypto;
    }

    /** @Groups({"Default", "API", "dev"}) */
    public function getCryptoDeploy(): Crypto
    {
        return $this->cryptoDeploy;
    }

    /** @Groups({"Default", "API", "dev"}) */
    public function getAddress(): ?string
    {
        return $this->address;
    }

    // Address is null for wrapped cryptos that use another crypto as native internal coin. For ex.: Arbitrum uses ETH.
    /** @Groups({"Default", "API", "dev"}) */
    public function isNative(): bool
    {
        return null === $this->address;
    }

    /** @Groups({"Default", "API", "dev"}) */
    public function getFee(): Money
    {
        return new Money($this->fee, new Currency($this->feeCurrency));
    }

    public function setFee(Money $fee): self
    {
        $this->fee = $fee->getAmount();

        return $this;
    }

    public function getFeeCurrency(): string
    {
        return $this->feeCurrency;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }
}
