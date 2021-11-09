<?php declare(strict_types = 1);

namespace App\Entity\Message;

use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity
 * @codeCoverageIgnore
 */
class MessageMetadata
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Message\Message", inversedBy="metadata")
     * @ORM\JoinColumn(nullable=false)
     * @var Message
     */
    private $message;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(nullable=false)
     * @var User
     */
    private $participant;

    /**
     * @ORM\Column(type="boolean", options={"default": 0})
     * @var bool
     */
    private $isRead = false;

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
}
