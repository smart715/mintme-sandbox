<?php declare(strict_types = 1);

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(
 *     name="user_cryptos",
 *     uniqueConstraints={@UniqueConstraint(name="user_crypto_index", columns={"user_id", "crypto_id"})}
 * )
 * @codeCoverageIgnore
 */
class UserCrypto implements UserTradebleInterface
{
    public function __construct(User $user, Crypto $crypto)
    {
        $this->user = $user;
        $this->crypto = $crypto;
    }

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="cryptos")
     * @ORM\JoinColumn(nullable=false)
     * @var User
     */
    protected $user;

    /**
     * @ORM\ManyToOne(targetEntity="Crypto", inversedBy="users")
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

    public function getCrypto(): Crypto
    {
        return $this->crypto;
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
