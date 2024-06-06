<?php declare(strict_types = 1);

namespace App\Entity;

use App\Entity\Token\Token;
use App\Repository\CommentTipRepository;
use App\Utils\Symbols;
use Doctrine\ORM\Mapping as ORM;
use Money\Currency;
use Money\Money;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=CommentTipRepository::class)
 * @ORM\HasLifecycleCallbacks()
 * @codeCoverageIgnore
 */
class CommentTip extends PromotionHistory
{
    public const FEE_TIP_TYPE = 'fee';
    public const TIP_TYPE = 'tip';

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $amount = '0'; // phpcs:ignore

    /**
     * @ORM\ManyToOne(targetEntity=Comment::class, inversedBy="tips")
     * @ORM\JoinColumn(nullable=false)
     */
    private Comment $comment;

    /**
     * @ORM\ManyToOne(targetEntity=Token::class, inversedBy="tips")
     * @ORM\JoinColumn(nullable=false)
     */
    private Token $token;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="tips")
     * @ORM\JoinColumn(nullable=false)
     */
    private User $commentAuthor;

    /**
     * @ORM\Column(type="string", nullable=false, options={"default": Symbols::TOK})
     */
    private string $currency = Symbols::TOK; // phpcs:ignore

    /**
     * @ORM\Column(type="string", nullable=false, options={"default": CommentTip::TIP_TYPE})
     */
    private string $tipType = self::TIP_TYPE; // phpcs:ignore

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @Groups({"Default", "API", "API_BASIC"});
     */
    public function getAmount(): Money
    {
        return new Money($this->amount, new Currency($this->currency));
    }


    public function setAmount(Money $amount): self
    {
        $this->amount = $amount->getAmount();

        return $this;
    }

    public function getComment(): Comment
    {
        return $this->comment;
    }

    public function setComment(Comment $comment): self
    {
        $this->comment = $comment;
        $this->setCommentAuthor($comment->getAuthor());

        return $this;
    }

    /**
     * @Groups({"Default", "API"});
     */
    public function getToken(): Token
    {
        return $this->token;
    }

    /**
     * @Groups({"API_BASIC"});
     */
    public function getTokenName(): string
    {
        return $this->token->getName();
    }

    /**
     * @Groups({"API_BASIC"});
     */
    public function getTokenImage(): ?Image
    {
        return $this->token->getImage();
    }

    public function setToken(Token $token): self
    {
        $this->token = $token;

        return $this;
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

    public function setCommentAuthor(User $user): self
    {
        $this->commentAuthor = $user;

        return $this;
    }

    /**
     * @Groups({"Default", "API"});
     */
    public function getCommentAuthor(): User
    {
        return $this->commentAuthor;
    }

    /**
     * @ORM\PrePersist
     */
    public function setCreatedAt(): self
    {
        $this->createdAt = new \DateTimeImmutable();

        return $this;
    }

    public function getType(): string
    {
        return self::COMMENT_TIP;
    }

    /**
     * @Groups({"Default", "API"});
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * @Groups({"Default", "API"});
     */
    public function getTipType(): string
    {
        return $this->tipType;
    }

    public function setTipType(string $tipType): self
    {
        $this->tipType = $tipType;

        return $this;
    }
}
