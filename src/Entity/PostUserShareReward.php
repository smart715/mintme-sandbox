<?php declare(strict_types = 1);

namespace App\Entity;

use App\Entity\Token\Token;
use Doctrine\ORM\Mapping as ORM;
use Money\Money;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PostUserShareRewardRepository")
 * @ORM\Table(
 *     name="post_users_share_reward",
 *     indexes={
 *         @ORM\Index(name="FK_SR_Posts", columns={"post_id"}),
 *         @ORM\Index(name="FK_SR_Users", columns={"user_id"})
 *     }
 * )
 * @ORM\HasLifecycleCallbacks()
 * @codeCoverageIgnore
 */
class PostUserShareReward extends PromotionHistory
{
    /**
     * @ORM\ManyToOne (targetEntity="App\Entity\Post", inversedBy="userShareRewards"))
     * @ORM\JoinColumn(name="post_id", nullable=false, onDelete="CASCADE")
     */
    private Post $post;

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getPost(): ?Post
    {
        return $this->post;
    }

    public function setPost(Post $post): self
    {
        $this->post = $post;

        return $this;
    }

    /** @ORM\PrePersist() */
    public function setCreatedAt(): self
    {
        $this->createdAt = new \DateTimeImmutable();

        return $this;
    }

    public function getToken(): Token
    {
        return $this->getPost()->getToken();
    }

    public function getAmount(): Money
    {
        return $this->getPost()->getShareReward();
    }

    public function getType(): string
    {
        return self::SHARE_POST;
    }
}
