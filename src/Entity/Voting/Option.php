<?php declare(strict_types = 1);

namespace App\Entity\Voting;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @codeCoverageIgnore
 * @ORM\Entity(repositoryClass="App\Repository\VotingOptionRepository")
 */
class Option
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotNull
     * @Assert\Length(min="1", max="32")
     */
    private string $title = ''; // phpcs:ignore

    /**
     * @ORM\ManyToOne(
     *     targetEntity="App\Entity\Voting\Voting",
     *     inversedBy="options",
     *     cascade={"persist", "remove"}
     * )
     * @ORM\JoinColumn(name="voting_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private Voting $voting;

    /**
     * @Groups({"Default", "API", "API_BASIC"})
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @Groups({"Default", "API", "API_BASIC"})
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getVoting(): Voting
    {
        return $this->voting;
    }

    public function setVoting(Voting $voting): self
    {
        $this->voting = $voting;

        return $this;
    }
}
