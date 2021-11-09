<?php declare(strict_types = 1);

namespace App\Entity\Token;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity
 */
class DiscordConfig
{
    /**
     * @ORM\Id
     * @ORM\OneToOne(targetEntity="Token", inversedBy="discordConfig")
     */
    private Token $token;

    /**
     * @ORM\Column(type="bigint", nullable=true, options={"default"=null})
     */
    private ?int $guildId = null; // phpcs:ignore

    /**
     * @ORM\Column(type="boolean", nullable=false, options={"default"=false})
     */
    private bool $specialRolesEnabled = false; // phpcs:ignore

    /**
     * @ORM\Column(type="boolean", nullable=false, options={"default"=false})
     */
    private bool $enabled = false; // phpcs:ignore

    public function getToken(): Token
    {
        return $this->token;
    }

    public function setToken(Token $token): self
    {
        $this->token = $token;

        return $this;
    }

    /** @Groups({"API"}) */
    public function getGuildId(): ?int
    {
        return $this->guildId;
    }

    public function setGuildId(?int $guildId): self
    {
        $this->guildId = $guildId;

        return $this;
    }

    public function hasGuild(): bool
    {
        return null !== $this->guildId;
    }

    /** @Groups({"API"}) */
    public function getSpecialRolesEnabled(): bool
    {
        return $this->specialRolesEnabled;
    }

    public function setSpecialRolesEnabled(bool $specialRolesEnabled): self
    {
        $this->specialRolesEnabled = $specialRolesEnabled;

        return $this;
    }

    /** @Groups({"API"}) */
    public function getEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function disable(): self
    {
        $this->enabled = false;
        $this->specialRolesEnabled = false;

        return $this;
    }
}
