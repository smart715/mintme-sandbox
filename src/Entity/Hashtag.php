<?php declare(strict_types = 1);

namespace App\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @codeCoverageIgnore
 * @ORM\Entity
 * @ORM\Table(name="hashtag",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="UQ_hashtag", columns={"value"})
 *     }
 * )
 * @ORM\HasLifecycleCallbacks()
 */
class Hashtag
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected int $id;

    /**
     * @ORM\Column(type="string", length=100)
     * @Assert\NotBlank
     * @Assert\Length(
     *     min = 1,
     *     max = 100,
     * )
     */
    protected string $value;

    /**
     * @ORM\Column(name="updated_at", type="datetime_immutable")
     */
    protected \DateTimeImmutable $updatedAt;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Post", mappedBy="hashtags", fetch="EXTRA_LAZY")
     */
    protected Collection $posts;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Comment", mappedBy="hashtags", fetch="EXTRA_LAZY")
     */
    protected Collection $comments;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function setUpdatedAt(): self
    {
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }
}
