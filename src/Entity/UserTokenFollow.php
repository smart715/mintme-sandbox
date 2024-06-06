<?php declare(strict_types = 1);

namespace App\Entity;

use App\Entity\Token\Token;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @codeCoverageIgnore
 * @ORM\Entity()
 * @ORM\Table(
 *     name="user_token_follow",
 *     uniqueConstraints={@UniqueConstraint(name="user_token_follow_index", columns={"user_id", "token_id"})}
 * )
 */
class UserTokenFollow
{
    public const FOLLOW_STATUS_FOLLOWED = 'followed';
    public const FOLLOW_STATUS_UNFOLLOWED = 'unfollowed';
    public const FOLLOW_STATUS_NEUTRAL = 'neutral';

    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected int $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Token\Token")
     * @ORM\JoinColumn(name="token_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected Token $token;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected User $user;

    /**
     * @ORM\Column(type="string")
     */
    protected string $followStatus;

    public function __construct()
    {
        $this->followStatus = UserTokenFollow::FOLLOW_STATUS_NEUTRAL;
    }

    /**
     * @Groups({"API", "Default"})
     */
    public function getToken(): Token
    {
        return $this->token;
    }

    public function setToken(Token $token): self
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @Groups({"API", "Default"})
     */
    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @Groups({"API", "Default"})
     */
    public function getFollowStatus(): string
    {
        return $this->followStatus;
    }

    public function setFollowStatus(string $followStatus): self
    {
        $this->followStatus = $followStatus;

        return $this;
    }
}
