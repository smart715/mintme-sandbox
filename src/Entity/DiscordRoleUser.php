<?php declare(strict_types = 1);

namespace App\Entity;

use App\Entity\Token\Token;
use Doctrine\ORM\Mapping as ORM;

/**
 * @codeCoverageIgnore
 * @ORM\Table(name="discord_role_user",
 *     indexes={
 *         @ORM\Index(name="FK_Discord_Role_User_Token", columns={"token_id"}),
 *         @ORM\Index(name="FK_Discord_Role_User_Role", columns={"role_id"})
 *     }
 * )
 * @ORM\Entity
 */
class DiscordRoleUser
{
    /**
     * @ORM\Id()
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="discordRoles")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected User $user;

    /**
     * @ORM\Id()
     * @ORM\ManyToOne(targetEntity="App\Entity\Token\Token")
     * @ORM\JoinColumn(name="token_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected Token $token;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\DiscordRole", inversedBy="users")
     * @ORM\JoinColumn(name="role_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected DiscordRole $discordRole;

    public function setDiscordRole(DiscordRole $role): self
    {
        $this->token = $role->getToken();
        $this->discordRole = $role;

        return $this;
    }

    public function getDiscordRole(): DiscordRole
    {
        return $this->discordRole;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getToken(): Token
    {
        return $this->token;
    }
}
