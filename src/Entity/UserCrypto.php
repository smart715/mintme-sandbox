<?php declare(strict_types = 1);

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="user_crypto")
 */
class UserCrypto implements UserTradebleInterface
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="relatedUsers")
     * @ORM\JoinColumn(nullable=false)
     * @var User
     */
    protected $user;

    /**
     * @ORM\ManyToOne(targetEntity="Crypto", inversedBy="relatedCryptos")
     * @ORM\JoinColumn(nullable=false)
     * @var Crypto
     */
    protected $crypto;

    /**
     * @ORM\Column(type="datetime_immutable")
     * @var DateTimeImmutable
     */
    protected $created;

    public function getId(): int
    {
        return $this->id;
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

    public function getCrypto(): Crypto
    {
        return $this->crypto;
    }

    public function setCrypto(Crypto $crypto): self
    {
        $this->crypto = $crypto;

        return $this;
    }

    public function getCreated(): DateTimeImmutable
    {
        return $this->created;
    }

    /** @ORM\PrePersist() */
    public function setCreatedValue(): self
    {
        $this->created = new DateTimeImmutable();

        return $this;
    }
}
