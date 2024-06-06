<?php declare(strict_types = 1);

namespace App\Entity;

use App\Entity\Token\Token;
use App\Repository\TokenSignupBonusCodeRepository;
use App\Utils\Symbols;
use Doctrine\ORM\Mapping as ORM;
use Money\Currency;
use Money\Money;

/**
 * @ORM\Entity(repositoryClass=TokenSignupBonusCodeRepository::class)
 * @ORM\Table(name="token_signup_bonus_code",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="UNIQ_BF14627941DEE7B9", columns={"token_id"})
 *     }
 * )
 * @codeCoverageIgnore
 */
class TokenSignupBonusCode
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity=Token::class, inversedBy="signUpBonusCode", cascade={"persist"})
     * @ORM\JoinColumn(name="token_id", nullable=false)
     */
    private Token $token;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private string $amount;

    /**
     * @ORM\Column(type="integer", length=100)
     */
    private int $participants;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private string $code;

    /**
     * @ORM\Column(name="locked_amount", type="string", length=100)
     */
    private string $lockedAmount;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getAmount(): Money
    {
        return new Money($this->amount, new Currency(Symbols::TOK));
    }

    public function setAmount(Money $amount): self
    {
        $this->amount = $amount->getAmount();

        return $this;
    }

    public function getParticipants(): ?int
    {
        return $this->participants;
    }

    public function setParticipants(int $participants): self
    {
        $this->participants = $participants;

        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getLockedAmount(): Money
    {
        return new Money($this->lockedAmount, new Currency(Symbols::TOK));
    }

    public function setLockedAmount(Money $lockedAmount): self
    {
        $this->lockedAmount = $lockedAmount->getAmount();

        return $this;
    }
}
