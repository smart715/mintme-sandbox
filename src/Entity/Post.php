<?php declare(strict_types = 1);

namespace App\Entity;

use App\Entity\Token\Token;
use Money\Currency;
use Money\Money;

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
     * @var string
     */
    protected $content;

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
     * @ORM\JoinColumn(name="quote_token_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @var Token
     */
    protected $token;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    protected $amount;

    public function getId(): int
    {
        return $this->id;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getToken(): Token
    {
        return $this->token;
    }

    public function getAmount(): Money
    {
        return new Money($this->amount, new Currency(Token::TOK_SYMBOL));
    }
}
