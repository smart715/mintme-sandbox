<?php declare(strict_types = 1);

namespace App\Entity;

use App\Repository\LikeRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=LikeRepository::class)
 * @ORM\Table(
 *  name="`like`",
 *  indexes={
 *     @ORM\Index(name="fk_ac6340b3f8697d13", columns={"comment_id"}),
 *  },
 *  uniqueConstraints={
 *      @ORM\UniqueConstraint(
 *          name="user_comment",
 *          columns={"user_id", "comment_id"}
 *      )
 *  }
 * )
 * @codeCoverageIgnore
 */
class Like
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="likes")
     * @ORM\JoinColumn(nullable=false)
     * @var User
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity=Comment::class, inversedBy="likes")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     * @var Comment
     */
    private $comment;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getComment(): ?Comment
    {
        return $this->comment;
    }

    public function setComment(?Comment $comment): self
    {
        $this->comment = $comment;

        return $this;
    }
}
