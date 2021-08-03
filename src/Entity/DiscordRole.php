<?php declare(strict_types = 1);

namespace App\Entity;

use App\Entity\Token\Token;
use App\Utils\Symbols;
use App\Validator\Constraints\Between;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Money\Currency;
use Money\Money;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @codeCoverageIgnore
 * @ORM\Table(name="discord_role",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="UQ_DiscordRole_Token_RequiredBalance",
 *             columns={"token_id", "required_balance"}
 *         )
 *     }
 * )
 * @ORM\Entity
 */
class DiscordRole
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected int $id;

    /**
     * @ORM\Column(type="bigint")
     */
    protected int $discordId;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Token\Token", inversedBy="discordRoles")
     * @ORM\JoinColumn(name="token_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected Token $token;

    /**
     * @ORM\Column(type="string")
     */
    protected string $requiredBalance = '0'; // phpcs:ignore

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @Assert\Length(
     *     min = 1,
     *     max = 100,
     * )
     */
    protected string $name = ''; // phpcs:ignore

    /** @ORM\Column(type="integer") */
    protected int $color = 0; // phpcs:ignore

    protected bool $changed = false; // phpcs:ignore

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\DiscordRoleUser", mappedBy="discordRole")
     */
    protected PersistentCollection $users;

    public function getId(): int
    {
        return $this->id;
    }

    public function setDiscordId(int $id): self
    {
        $this->discordId = $id;

        return $this;
    }

    /**
     * @Groups({"Default", "API"})
     */
    public function getDiscordId(): int
    {
        return $this->discordId;
    }

    public function setName(string $name): self
    {
        if ($name !== $this->name) {
            $this->name = $name;
            $this->changed = true;
        }

        return $this;
    }

    /**
     * @Groups({"Default", "API"})
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function setToken(Token $token): self
    {
        $this->token = $token;

        return $this;
    }

    public function getToken(): Token
    {
        return $this->token;
    }

    public function setRequiredBalance(Money $amount): self
    {
        $this->requiredBalance = $amount->getAmount();

        return $this;
    }

    /**
     * @Between(
     *     min = 0.0001,
     *     max = 1000000
     * )
     * @Groups({"Default", "API"})
     */
    public function getRequiredBalance(): Money
    {
        return new Money($this->requiredBalance, new Currency(Symbols::TOK));
    }

    public function setColor(int $color): self
    {
        if ($color !== $this->color) {
            $this->color = $color;
            $this->changed = true;
        }

        return $this;
    }

    /**
     * @Groups({"Default", "API"})
     */
    public function getColor(): int
    {
        return $this->color;
    }

    public function hasChanged(): bool
    {
        return $this->changed;
    }

    public function getUsers(): Collection
    {
        return $this->users->map(fn (DiscordRoleUser $dru) => $dru->getUser());
    }

    public function update(DiscordRole $role): self
    {
        $this->setName($role->getName());
        $this->setColor($role->getColor());

        return $this;
    }
}
