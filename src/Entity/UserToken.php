<?php declare(strict_types = 1);

namespace App\Entity;

use App\Entity\Token\Token;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserTokenRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(
 *     name="user_tokens",
 *     uniqueConstraints={@UniqueConstraint(name="user_token_index", columns={"user_id", "token_id"})}
 *     )
 * @codeCoverageIgnore
 */
class UserToken implements UserTradableInterface
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="tokens")
     * @ORM\JoinColumn(nullable=false)
     * @var User
     */
    protected $user;

    /**
     * @ORM\Column(type="boolean", options={"default": true})
     */
    protected bool $isHolder = true; // phpcs:ignore

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Token\Token", inversedBy="users")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     * @var Token
     */
    protected $token;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     * @var DateTimeImmutable|null
     */
    protected $created;

    /**
     * @ORM\Column(type="boolean", nullable=false, options={"default" : false})
     */
    protected bool $isReferral = false; // phpcs:ignore

    /**
     * @ORM\Column(type="boolean", nullable=false, options={"default" : false})
     */
    protected bool $isRemoved = false; // phpcs:ignore

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

    public function getToken(): Token
    {
        return $this->token;
    }

    public function setToken(Token $token): self
    {
        $this->token = $token;

        return $this;
    }

    public function getCreated(): ?DateTimeImmutable
    {
        return $this->created;
    }

    /** @ORM\PrePersist() */
    public function setCreatedValue(): self
    {
        $this->created = new DateTimeImmutable();

        return $this;
    }

    public function isHolder(): bool
    {
        return $this->isHolder;
    }

    public function setIsHolder(bool $isHolder): self
    {
        $this->isHolder = $isHolder;

        return $this;
    }

    public function isReferral(): bool
    {
        return $this->isReferral;
    }

    public function setIsReferral(bool $isReferral): self
    {
        $this->isReferral = $isReferral;

        return $this;
    }

    public function isRemoved(): bool
    {
        return $this->isRemoved;
    }

    public function setIsRemoved(bool $isRemoved): self
    {
        $this->isRemoved = $isRemoved;

        return $this;
    }
}
