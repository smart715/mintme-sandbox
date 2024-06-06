<?php declare(strict_types = 1);

namespace App\Entity\Message;

use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="message_metadata",
 *     indexes={
 *         @ORM\Index(name="IDX_IS_READ", columns={"is_read"}),
 *     }
 * )
 * @codeCoverageIgnore
 */
class MessageMetadata
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Message\Message", inversedBy="metadata")
     * @ORM\JoinColumn(nullable=false)
     */
    private Message $message;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(nullable=false)
     */
    private User $participant;

    /**
     * @ORM\Column(type="boolean", options={"default": 0})
     */
    private bool $isRead = false; // phpcs:ignore

    /**
     * @ORM\Column(type="boolean", options={"default": 0})
     */
    private bool $isDeleted = false; // phpcs:ignore

    public function getId(): int
    {
        return $this->id;
    }

    public function setMessage(Message $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getMessage(): Message
    {
        return $this->message;
    }

    public function setParticipant(User $participant): self
    {
        $this->participant = $participant;

        return $this;
    }

    /**
     * @Groups({"Default"})
     */
    public function getParticipant(): User
    {
        return $this->participant;
    }

    public function setRead(): self
    {
        $this->isRead = true;

        return $this;
    }

    public function isRead(): bool
    {
        return $this->isRead;
    }

    public function setDeleted(): self
    {
        $this->isDeleted = true;

        return $this;
    }

    public function isDeleted(): bool
    {
        return $this->isDeleted;
    }
}
