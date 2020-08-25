<?php declare(strict_types = 1);

namespace App\Entity;

use App\Validator\Constraints\NotEmptyWithoutBbcodes;
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

    public function getId(): int
    {
        return $this->id;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

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

    public function setPost(Post $post): self
    {
        $this->post = $post;

        return $this;
    }

    /** @Groups({"Default", "API"}) */
    public function getPost(): Post
    {
        return $this->post;
    }

    public function setAuthor(User $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getAuthor(): User
    {
        return $this->author;
    }
}
