<?php declare(strict_types = 1);

namespace App\Entity;

use App\Entity\Token\Token;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="user_token")
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
     * @ORM\ManyToOne(targetEntity="User", inversedBy="relatedUsers")
     * @ORM\JoinColumn(nullable=false)
     * @var User
     */
    protected $user;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Token\Token", inversedBy="relatedTokens")
     * @ORM\JoinColumn(nullable=false)
     * @var Token
     */
    protected $token;

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

    public function getToken(): Token
    {
        return $this->token;
    }

    public function setToken(Token $token): self
    {
        $this->token = $token;

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
