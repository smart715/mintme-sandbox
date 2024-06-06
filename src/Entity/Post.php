<?php declare(strict_types = 1);

namespace App\Entity;

use App\Entity\Token\Token;
use App\Utils\Symbols;
use App\Validator\Constraints\Between;
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
 * @ORM\Table(
 *     name="post",
 *     indexes={
 *         @ORM\Index(name="FK_Posts_Tokens", columns={"token_id"}),
 *     }
 * )
 * @ORM\HasLifecycleCallbacks()
 */
class Post
{
    public const STATUS_ACTIVE = 1;
    public const STATUS_DELETED = 0;

    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected int $id;

    /**
     * @ORM\Column(type="text", length=60000, nullable=true)
     * @Assert\NotNull
     * @Assert\Length(
     *     min = 2,
     *     max = 1000,
     * )
     */
    protected string $content = ''; // phpcs:ignore -- for some reason, if the variable has a type and default value, phpcs says it's not used (but it is)

    /**
     * @ORM\Column(type="datetime_immutable", options={"default": "CURRENT_TIMESTAMP"})
     */
    protected \DateTimeImmutable $createdAt;

    /**
     * @ORM\Column(
     *     type="datetime_immutable",
     *     nullable=true,
     * )
     */
    protected ?\DateTimeImmutable $updatedAt;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Token\Token", inversedBy="posts")
     * @ORM\JoinColumn(name="token_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected Token $token;

    /**
     * @ORM\Column(type="string")
     */
    protected string $amount = '0'; // phpcs:ignore

    /**
     * @ORM\Column(type="string", options={"default": ""}, nullable=true)
     * @Assert\NotBlank
     * @Assert\Length(
     *     min = 1,
     *     max = 100,
     * )
     */
    protected string $title = ''; // phpcs:ignore

    /**
     * @ORM\Column(type="string", options={"default": "0"}, nullable=true)
     */
    protected string $shareReward = '0'; // phpcs:ignore

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $slug = null; // phpcs:ignore

    /**
     * @ORM\Column(type="integer", options={"default": 0}, nullable=true)
     */
    protected int $likes = 0; // phpcs:ignore

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Comment", mappedBy="post", fetch="EXTRA_LAZY", cascade={"remove"})
     * @ORM\JoinColumn(onDelete="CASCADE")
     * @ORM\OrderBy({"likeCount" = "DESC"})
     */
    protected Collection $comments;

    /**
     * @ORM\OneToMany(
     *     targetEntity="App\Entity\PostUserShareReward",
     *     mappedBy="post",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true,
     *     fetch="EXTRA_LAZY")
     */
    protected Collection $userShareRewards;

    /**
     * @ORM\Column(type="integer", nullable=false, options={"default": 1})
     */
    protected int $status = self::STATUS_ACTIVE; // phpcs:ignore

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\User", inversedBy="likedPosts", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="post_users_likes",
     *     joinColumns={@ORM\JoinColumn(name="post_id", referencedColumnName="id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")}
     *     )
     */
    protected Collection $usersLiked;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Hashtag", inversedBy="posts", fetch="EXTRA_LAZY")
     */
    protected Collection $hashtags;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->usersLiked = new ArrayCollection();
        $this->userShareRewards = new ArrayCollection();
        $this->hashtags = new ArrayCollection();
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
        return new Money($this->amount, new Currency(Symbols::TOK));
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
    public function getLikes(): int
    {
        return $this->likes;
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
        return new Money($this->shareReward, new Currency(Symbols::TOK));
    }

    public function setShareReward(Money $reward): self
    {
        $this->shareReward = $reward->getAmount();

        return $this;
    }

    public function addUserShareReward(PostUserShareReward $userShareReward): self
    {
        if (!$this->userShareRewards->contains($userShareReward)) {
            $this->userShareRewards->add($userShareReward);
        }

        return $this;
    }

    public function getUserShareRewards(): Collection
    {
        return $this->userShareRewards;
    }

    public function isUserAlreadyRewarded(User $user): bool
    {
        foreach ($this->userShareRewards as $userShareReward) {
            if ($user === $userShareReward->getUser()) {
                return true;
            }
        }

        return false;
    }

    public function addUserLike(User $user): self
    {
        $this->usersLiked->add($user);
        $this->increaseLikes();

        return $this;
    }

    public function removeUserLike(User $user): self
    {
        $this->usersLiked->removeElement($user);
        $this->decreaseLikes();

        return $this;
    }

    public function isUserAlreadyLiked(User $user): bool
    {
        return $this->usersLiked->contains($user);
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

    private function increaseLikes(): self
    {
        $this->likes++;

        return $this;
    }

    private function decreaseLikes(): self
    {
        $this->likes--;

        return $this;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function setHashtags(array $hashtags, bool $isEdit = false): self
    {
        $this->hashtags->clear();

        foreach ($hashtags as $hashtag) {
            if (!$isEdit) {
                $hashtag->setUpdatedAt();
            }

            $this->hashtags->add($hashtag);
        }

        return $this;
    }

    /**
     * @Groups({"Default", "API"})
     */
    public function getStatus(): int
    {
        return $this->status;
    }
}
