<?php declare(strict_types = 1);

namespace App\Entity\Message;

use App\Entity\Token\Token;
use App\Entity\User;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 * @codeCoverageIgnore
 */
class Thread
{
    public const TYPE_DM = 'DM';

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Token\Token", inversedBy="threads", cascade={"all"})
     * @ORM\JoinColumn(nullable=false)
     * @var Token
     */
    private $token;

    /**
     * @ORM\Column(type="string", nullable=false)
     * @var string
     */
    private $type;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Message\Message",  mappedBy="thread", cascade={"remove"})
     * @var Message[]|Collection
     */
    private $messages;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Message\ThreadMetadata", mappedBy="thread", cascade={"all"})
     * @var ThreadMetadata[]|Collection
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
     * @Groups({"Default"})
     */
    public function getId(): int
    {
        return $this->id;
    }

    public function setToken(Token $token): self
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @Groups({"Default"})
     */
    public function getToken(): Token
    {
        return $this->token;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @Groups({"Default"})
     */
    public function getType(): string
    {
        return $this->type;
    }

    public function getMessages(): array
    {
        return $this->messages->toArray();
    }

    public function addMetadata(ThreadMetadata $metadata): self
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

    public function hasParticipant(User $user): bool
    {
        return count(
            array_filter($this->getMetadata(), static fn (ThreadMetadata $threadMetadata)
            => $user->getId() === $threadMetadata->getParticipant()->getId())
        ) > 0;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
