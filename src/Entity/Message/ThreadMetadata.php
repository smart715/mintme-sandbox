<?php declare(strict_types = 1);

namespace App\Entity\Message;

use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity
 * @codeCoverageIgnore
 */
class ThreadMetadata
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Message\Thread", inversedBy="metadata")
     * @ORM\JoinColumn(nullable=false)
     * @var Thread
     */
    private $thread;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(nullable=false)
     * @var User
     */
    private $participant;

    /**
     * @ORM\Column(type="boolean", options={"default"=false})
     */
    private bool $isBlocked = false; // phpcs:ignore

    /**
     * @ORM\Column(type="boolean", options={"default"=false})
     */
    private bool $isHidden = false; // phpcs:ignore

    public function getId(): int
    {
        return $this->id;
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

    public function setIsBlocked(bool $value): self
    {
        $this->isBlocked = $value;

        return $this;
    }

    /**
     * @Groups({"Default"})
     */
    public function getIsBlocked(): bool
    {
        return $this->isBlocked;
    }

    public function setHidden(bool $isHidden): self
    {
        $this->isHidden = $isHidden;

        return $this;
    }

    /**
     * @Groups({"Default"})
     */
    public function isHidden(): bool
    {
        return $this->isHidden;
    }
}
