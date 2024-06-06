<?php declare(strict_types = 1);

namespace App\Entity;

use App\Entity\Token\Token;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\DepositHashRepository")
 * @ORM\Table(
 *     name="deposit_hash",
 *     indexes={
 *         @ORM\Index(name="IDX_E7405A6E9571A63", columns={"crypto_id"}),
 *         @ORM\Index(name="IDX_E7405A641DEE7B9", columns={"token_id"}),
 *         @ORM\Index(name="IDX_E7405A6A76ED395", columns={"user_id"})
 *    }
 * )
 * @codeCoverageIgnore
 */
class DepositHash
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\ManyToOne(targetEntity=Crypto::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private Crypto $crypto;

    /**
     * @ORM\ManyToOne(targetEntity=Token::class)
     * @ORM\JoinColumn(nullable=true)
     */
    private ?Token $token;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private User $user;

    /** @ORM\Column(type="string", length=255) */
    private string $hash;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getHash(): string
    {
        return $this->hash;
    }

    public function setHash(string $hash): self
    {
        $this->hash = $hash;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getToken(): ?Token
    {
        return $this->token;
    }

    public function setToken(?Token $token): self
    {
        $this->token = $token;

        return $this;
    }
}
