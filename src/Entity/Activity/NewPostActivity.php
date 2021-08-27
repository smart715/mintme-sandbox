<?php declare(strict_types = 1);

namespace App\Entity\Activity;

use App\Entity\Post;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @codeCoverageIgnore
 * @ORM\Entity
 */
class NewPostActivity extends Activity
{
    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Post")
     * @ORM\JoinColumn(name="post_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected ?Post $post;

    public function getType(): int
    {
        return self::NEW_POST;
    }

    public function setPost(Post $post): self
    {
        $this->post = $post;

        return $this;
    }

    /** @Groups({"Default", "API"}) */
    public function getPost(): ?Post
    {
        return $this->post;
    }
}
