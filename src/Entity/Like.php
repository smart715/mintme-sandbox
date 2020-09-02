<?php declare(strict_types = 1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @codeCoverageIgnore
 * @ORM\Entity
 * @ORM\Table(name="`like`")
 */
class Like
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Comment", inversedBy="likes")
     * @ORM\JoinColumn(name="comment_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @var Comment
     */
    protected $comment;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="likes")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @var User
     */
    protected $user;

    public function getId(): int
    {
        return $this->id;
    }

    public function setComment(Comment $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getComment(): Comment
    {
        return $this->comment;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
