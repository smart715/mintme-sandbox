<?php declare(strict_types = 1);

namespace App\Entity;

use App\Entity\Token\Token;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(
 *     name="user_tokens",
 *     uniqueConstraints={@UniqueConstraint(name="user_token_index", columns={"user_id", "token_id"})}
 *     )
 * @codeCoverageIgnore
 */
class UserToken implements UserTradebleInterface
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="users")
     * @ORM\JoinColumn(nullable=false)
     * @var User
     */
    protected $user;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Token\Token", inversedBy="tokens")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     * @var Token
     */
    protected $token;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     * @var DateTimeImmutable|null
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
}
