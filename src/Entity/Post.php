<?php declare(strict_types = 1);

namespace App\Entity;

use App\Entity\Token\Token;
use App\Validator\Constraints\Between;
use App\Validator\Constraints\NotEmptyWithoutBbcodes;
use App\Validator\Constraints\PositiveAmount;
use Doctrine\ORM\Mapping as ORM;
use Money\Currency;
use Money\Money;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PostRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Post
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=60000)
     * @Assert\NotNull
     * @NotEmptyWithoutBbcodes
     * @Assert\Length(
     *     min = 2,
     *     max = 500,
     * )
     * @var string
     */
    protected $content = '';

    /**
     * @ORM\Column(type="datetime_immutable")
     * @var \DateTimeImmutable
     */
    protected $createdAt;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     * @var \DateTimeImmutable|null
     */
    protected $updatedAt;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Token\Token", inversedBy="posts")
     * @ORM\JoinColumn(name="token_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @var Token
     */
    protected $token;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    protected $amount = '0';

    /**
     * @Groups({"Default", "API"})
     */
    public function getId(): int
    {
        return $this->id;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    /**
     * @Groups({"Default", "API"})
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @ORM\PrePersist
     */
    public function setCreatedAt(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    /**
     * @Groups({"Default", "API"})
     */
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @ORM\PreUpdate
     */
    public function setUpdatedAt(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setToken(Token $token): void
    {
        $this->token = $token;
    }

    /** @Groups({"Default", "API"}) */
    public function getToken(): Token
    {
        return $this->token;
    }

    public function setAmount(Money $amount): void
    {
        $this->amount = $amount->getAmount();
    }

    /**
     * @Between(
     *     min = 0,
     *     max = 999999.9999
     * )
     * @Groups({"Default", "API"});
     */
    public function getAmount(): Money
    {
        return new Money($this->amount, new Currency(Token::TOK_SYMBOL));
    }

    /** @Groups({"Default", "API"}) */
    public function getAuthor(): ?Profile
    {
        return $this->getToken()->getProfile();
    }
}
