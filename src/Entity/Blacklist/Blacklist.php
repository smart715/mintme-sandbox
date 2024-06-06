<?php declare(strict_types = 1);

namespace App\Entity\Blacklist;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(uniqueConstraints={
 *     @ORM\UniqueConstraint(name="assignment_unique", columns={"type", "value"})
 * })
 * @ORM\Entity(repositoryClass="App\Repository\BlacklistRepository")
 * @codeCoverageIgnore
 */
class Blacklist
{
    public const CRYPTO_NAME = 'crypto-name';
    public const CRYPTO_SYMBOL = 'crypto-symbol';
    public const TOKEN = 'token';
    public const EMAIL = 'email';
    public const PHONE = 'phone';
    public const AIRDROP_DOMAIN = 'airdrop-domain';
    public const NICKNAME = 'nickname';
    public const TOKEN_TYPES = [
        self::TOKEN,
        self::CRYPTO_SYMBOL,
        self::CRYPTO_NAME,
    ];
    public const BLACKLISTED_WORDS = 'blacklisted-word';
    public const WHITELISTED_WORDS = 'whitelisted-word';

    public const SMS_D7_PROVIDER = 'sms-d7';
    public const SMS_CLICKATELL_PROVIDER = 'sms-clickatell';

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(type="string", nullable=false)
     * @var string|null
     */
    protected $type;

    /**
     * @ORM\Column(type="string", nullable=false)
     * @var string|null
     */
    protected $value;

    public function __construct(string $value, string $type)
    {
        $this->value = $value;
        $this->type = $type;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getValue(): string
    {
        return $this->value ?? '';
    }

    public function getType(): string
    {
        return $this->type ?? '';
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;

        return $this;
    }
}
