<?php declare(strict_types = 1);

namespace App\Entity;

use App\Entity\Token\Token;
use App\Validator\Constraints\Between;
use App\Validator\Constraints\NotEmptyWithoutBbcodes;
use App\Validator\Constraints\PositiveAmount;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Money\Currency;
use Money\Money;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @codeCoverageIgnore
 * @ORM\Entity(repositoryClass="App\Repository\PostRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Post
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected int $id;

    /**
     * @ORM\Column(type="string", length=60000)
     * @Assert\NotNull
     * @NotEmptyWithoutBbcodes
     * @Assert\Length(
     *     min = 2,
     *     max = 1000,
     * )
     */
    protected string $content = ''; // phpcs:ignore -- for some reason, if the variable has a type and default value, phpcs says it's not used (but it is)

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    protected \DateTimeImmutable $createdAt;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    protected ?\DateTimeImmutable $updatedAt;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Token\Token", inversedBy="posts")
     * @ORM\JoinColumn(name="token_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected Token $token;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @Assert\Length(
     *     min = 1,
     *     max = 100,
     * )
     */
    protected string $amount = '0'; // phpcs:ignore

    /**
     * @ORM\Column(type="string")
     */
    protected string $title = ''; // phpcs:ignore

    /**
     * @ORM\Column(type="string")
     */
    protected string $shareReward = '0'; // phpcs:ignore

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $slug = null; // phpcs:ignore

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Comment", mappedBy="post", fetch="EXTRA_LAZY")
     * @ORM\OrderBy({"likeCount" = "DESC"})
     */
    protected Collection $comments;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\User", inversedBy="rewardClaimedPosts", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="post_users_share_reward",
     *     joinColumns={@ORM\JoinColumn(name="post_id", referencedColumnName="id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id",  onDelete="CASCADE")}
     *     )
     */
    protected Collection $rewardedUsers;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->rewardedUsers = new ArrayCollection();
    }

    /**
     * @Groups({"Default", "API"})
     */
    public function getId(): int
    {
        return $this->id;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
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
    public function setCreatedAt(): self
    {
        $this->createdAt = new \DateTimeImmutable();

        return $this;
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
    public function setUpdatedAt(): self
    {
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setToken(Token $token): self
    {
        $this->token = $token;

        return $this;
    }

    /** @Groups({"Default", "API"}) */
    public function getToken(): Token
    {
        return $this->token;
    }

    public function setAmount(Money $amount): self
    {
        $this->amount = $amount->getAmount();

        return $this;
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

    /**
     * @Groups({"Default", "API"})
     */
    public function getCommentsCount(): int
    {
        return $this->comments->count();
    }

    public function getComments(): array
    {
        return $this->comments->toArray();
    }

    public function addComment(Comment $comment): self
    {
        $this->comments->add($comment);

        return $this;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @Groups({"Default", "API"})
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @Between(
     *     min = 0,
     *     max = 100
     * )
     * @Groups({"Default", "API"});
     */
    public function getShareReward(): Money
    {
        return new Money($this->shareReward, new Currency(Token::TOK_SYMBOL));
    }

    public function setShareReward(Money $reward): self
    {
        $this->shareReward = $reward->getAmount();

        return $this;
    }

    public function addRewardedUser(User $user): self
    {
        $this->rewardedUsers->add($user);

        return $this;
    }

    public function getRewardedUsers(): Collection
    {
        return $this->getRewardedUsers();
    }

    public function isUserAlreadyRewarded(User $user): bool
    {
        return $this->rewardedUsers->contains($user);
    }

    /**
     * @Groups({"Default", "API"})
     */
    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }
}
