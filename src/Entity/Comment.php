<?php declare(strict_types = 1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @codeCoverageIgnore
 * @ORM\Entity
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
     * @ORM\Column(type="string", length=60000)
     * @Assert\NotNull
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
     * @ORM\ManyToMany(targetEntity="App\Entity\User", inversedBy="likes", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="`like`",
     *      joinColumns={@ORM\JoinColumn(name="comment_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id",  onDelete="CASCADE")}
     *      )
     * @var ArrayCollection
     */
    protected $likes;

    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    protected $likeCount = 0;

    public function __construct()
    {
        $this->likes = new ArrayCollection();
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

    /**
     * @Groups({"Default", "API"})
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

    public function setAuthor(User $author): self
    {
        $this->author = $author;

        return $this;
    }

    /**
     * @Groups({"Default", "API"})
     */
    public function getAuthor(): User
    {
        return $this->author;
    }

    /**
     * @Groups({"Default", "API"})
     */
    public function getLikeCount(): int
    {
        return $this->likeCount;
    }

    public function getLikedBy(User $user): bool
    {
        return $this->likes->contains($user);
    }

    public function removeLike(User $user): self
    {
        $this->likes->removeElement($user);
        $this->likeCount--;

        return $this;
    }

    public function addLike(User $user): self
    {
        $this->likes->add($user);
        $this->likeCount++;

        return $this;
    }
}
