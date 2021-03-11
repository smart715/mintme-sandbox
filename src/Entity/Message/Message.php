<?php declare(strict_types = 1);

namespace App\Entity\Message;

use App\Entity\User;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 * @codeCoverageIgnore
 */
class Message
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="App\Entity\Message\Thread",
     *     inversedBy="messages",
     *     cascade={"all"}
     *     )
     * @ORM\JoinColumn(nullable=false)
     * @var Thread
     */
    private $thread;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(nullable=false)
     * @var User
     */
    private $sender;

    /**
     * @ORM\Column(type="text", length=500, nullable=false)
     * @var string
     */
    private $body;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Message\MessageMetadata", mappedBy="message", cascade={"all"})
     * @var MessageMetadata[]|Collection
     */
    private $metadata;

    /**
     * @ORM\Column(type="datetime_immutable")
     * @var \DateTimeImmutable
     */
    private $createdAt;

    /** @ORM\PrePersist() */
    public function setCreatedAt(): self
    {
        $this->createdAt = new \DateTimeImmutable();

        return $this;
    }

    /**
     * @Groups({"API"})
     */
    public function getId(): int
    {
        return $this->id;
    }

    public function setSender(User $sender): self
    {
        $this->sender = $sender;

        return $this;
    }

    /**
     * @Groups({"API"})
     */
    public function getSender(): User
    {
        return $this->sender;
    }

    public function setBody(string $body): self
    {
        $this->body = $body;

        return $this;
    }

    /**
     * @Groups({"API"})
     */
    public function getBody(): string
    {
        return $this->body;
    }

    public function setThread(Thread $thread): self
    {
        $this->thread = $thread;

        return $this;
    }

    public function getThread(): Thread
    {
        return $this->thread;
    }
    
    public function addMetadata(MessageMetadata $metadata): self
    {
        $this->metadata[] = $metadata;

        return $this;
    }

    /**
     * @Groups({"Default"})
     */
    public function getMetadata(): array
    {
        return $this->metadata->toArray();
    }

    /**
     * @Groups({"API"})
     */
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
