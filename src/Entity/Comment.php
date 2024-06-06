<?php declare(strict_types = 1);

namespace App\Entity;

use App\Entity\Token\Token;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Money\Money;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @codeCoverageIgnore
 * @ORM\Entity
 * @ORM\Table(
 *     name="comment",
 *     indexes={
 *         @ORM\Index(name="fk_comments_posts", columns={"post_id"}),
 *         @ORM\Index(name="fk_comments_users", columns={"user_id"})
 *     }
 * )
 * @ORM\HasLifecycleCallbacks()
 */
class Comment
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(type="text", length=60000, nullable=true)
     * @Assert\NotNull
     * @Assert\Length(
     *     min = 2,
     *     max = 500,
     * )
     * @var string
     */
    protected $content = '';

    /**
     * @ORM\Column(type="datetime_immutable", options={"default": "CURRENT_TIMESTAMP"})
     * @var \DateTimeImmutable
     */
    protected $createdAt;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     * @var \DateTimeImmutable|null
     */
    protected $updatedAt;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Post", inversedBy="comments")
     * @ORM\JoinColumn(name="post_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @var Post
     */
    protected $post;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="comments")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @var User
     */
    protected $author;

    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    protected $likeCount = 0;

    /**
     * @ORM\OneToMany(
     *  targetEntity=Like::class,
     *  mappedBy="comment",
     *  cascade={"persist","remove"},
     *  orphanRemoval=true,
     *  fetch="EXTRA_LAZY"
     * )
     */
    private ?Collection $likes;

    /**
     * @ORM\OneToMany(
     *   targetEntity=CommentTip::class,
     *   mappedBy="comment",
     *   cascade={"persist","remove"},
     *   fetch="EXTRA_LAZY"
     * )
     * */
    private ?Collection $tips; // phpcs:ignore

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Hashtag", inversedBy="comments", fetch="EXTRA_LAZY")
     */
    protected Collection $hashtags;

    public function __construct()
    {
        $this->likes = new ArrayCollection();
        $this->tips = new ArrayCollection();
        $this->hashtags = new ArrayCollection();
    }

    /**
     * @Groups({"Default", "API", "API_BASIC"})
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
     * @Groups({"Default", "API", "API_BASIC"})
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
     * @Groups({"Default", "API", "API_BASIC"})
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

    /**
     * @Groups({"Default", "API", "API_BASIC"})
     */
    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setPost(Post $post): self
    {
        $this->post = $post;

        return $this;
    }

    public function getPost(): Post
    {
        return $this->post;
    }

    /**
     * @Groups({"Default", "API", "API_BASIC"})
     */
    public function getPostId(): int
    {
        return $this->post->getId();
    }

    /**
     * @Groups({"Default", "API", "API_BASIC"})
     */
    public function getPostAmount(): Money
    {
        return $this->post->getAmount();
    }

    /**
     * @Groups({"Default", "API", "API_BASIC"})
     */
    public function getToken(): Token
    {
        return $this->post->getToken();
    }

    public function setAuthor(User $author): self
    {
        $this->author = $author;

        return $this;
    }

    /**
     * @Groups({"Default", "API", "API_BASIC"})
     */
    public function getAuthor(): User
    {
        return $this->author;
    }

    /**
     * @Groups({"Default", "API", "API_BASIC"})
     */
    public function getLikeCount(): int
    {
        return $this->likeCount;
    }

    public function getLikedBy(User $user): ?Like
    {
        $criteria = Criteria::create()
            ->andWhere(Criteria::expr()->eq('user', $user));

        /** @psalm-suppress UndefinedInterfaceMethod */
        return $this->likes->matching($criteria)[0];
    }

    public function getLikes(): ?Collection
    {
        return $this->likes;
    }

    public function addLike(Like $like): self
    {
        if (!$this->likes->contains($like)) {
            $this->likes->add($like);
            $like->setComment($this);
            $this->likeCount++;
        }

        return $this;
    }

    public function removeLike(Like $like): self
    {
        if ($this->likes->contains($like)) {
            $this->likes->removeElement($like);

            if ($like->getComment() === $this) {
                $like->setComment(null);
                $this->likeCount--;
            }
        }

        return $this;
    }

    /**
     * @Groups({"Default", "API", "API_BASIC"})
     */
    public function getTips(): Collection
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('tipType', CommentTip::TIP_TYPE));

        return $this->tips->matching($criteria);
    }

    public function setHashtags(array $hashtags, bool $updateTimestamps = false): self
    {
        $this->hashtags->clear();

        foreach ($hashtags as $hashtag) {
            if ($updateTimestamps) {
                $hashtag->setUpdatedAt();
            }

            $this->hashtags->add($hashtag);
        }

        return $this;
    }
}
