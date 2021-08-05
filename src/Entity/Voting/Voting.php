<?php declare(strict_types = 1);

namespace App\Entity\Voting;

use App\Entity\Profile;
use App\Entity\User;
use App\Validator\Constraints\DateTimeMin;
use App\Validator\Constraints\NotEmptyWithoutBbcodes;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @codeCoverageIgnore
 * @ORM\Entity(repositoryClass="App\Repository\VotingRepository")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({"voting"="Voting", "tokenVoting"="TokenVoting", "cryptoVoting"="CryptoVoting"})
 * @ORM\HasLifecycleCallbacks()
 */
abstract class Voting
{
    private const ONE_HOUR_IN_SEC = 3600;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    protected int $id;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotNull
     * @Assert\Length(min="5", max="100")
     * @Groups({"Default", "API"})
     */
    private string $title = ''; // phpcs:ignore

    /**
     * @ORM\Column(type="string", length=1000)
     * @Assert\NotNull
     * @NotEmptyWithoutBbcodes
     * @Assert\Length(min="100", max="1000")
     */
    private string $description = ''; // phpcs:ignore

    /**
     * @ORM\Column(type="datetime_immutable")
     * @Assert\DateTime
     * @DateTimeMin(modify="+1 minutes")
     */
    private ?\DateTimeImmutable $endDate = null; // phpcs:ignore

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $slug = null; // phpcs:ignore

    /**
     * @ORM\OneToMany(
     *     targetEntity="App\Entity\Voting\Option",
     *     mappedBy="voting",
     *     cascade={"persist", "remove"}
     * )
     * @Assert\Count(min="2", max="32")
     * @var ArrayCollection
     */
    private $options;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="App\Entity\User",
     *     inversedBy="votings",
     *     cascade={"persist", "remove"}
     * )
     * @ORM\JoinColumn(name="creator_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private User $creator;

    /**
     * @ORM\OneToMany(
     *     targetEntity="App\Entity\Voting\UserVoting",
     *     mappedBy="voting",
     *     cascade={"persist", "remove"}
     * )
     * @var ArrayCollection|null
     */
    private $userVotings;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->options = new ArrayCollection();
    }

    /**
     * @Groups({"Default", "API"})
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @Groups({"Default", "API"})
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

    /**
     * @Groups({"Default", "API"})
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @Groups({"Default", "API"})
     */
    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeImmutable $endDate): self
    {
        if ($endDate->getTimestamp() - time() < self::ONE_HOUR_IN_SEC) {
            $endDate = new \DateTimeImmutable('+1 hour');
        }

        $this->endDate = $endDate;

        return $this;
    }

    /**
     * @Groups({"Default", "API"})
     */
    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * @Groups({"Default", "API"})
     */
    public function getOptions(): array
    {
        return $this->options->toArray();
    }

    public function setOptions(ArrayCollection $options): self
    {
        $this->options = $options;

        return $this;
    }

    public function addOption(Option $option): self
    {
        $option->setVoting($this);
        $this->options->add($option);

        return $this;
    }

    public function removeOption(int $key): self
    {
        $this->options->remove($key);

        return $this;
    }

    /**
     * @ORM\PrePersist
     */
    public function setCreatedAt(): self
    {
        $this->createdAt = new \DateTimeImmutable();

        return $this;
    }

    /**
     * @Groups({"Default", "API"})
     */
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getCreator(): User
    {
        return $this->creator;
    }

    public function setCreator(User $creator): self
    {
        $this->creator = $creator;

        return $this;
    }

    public function isCreator(User $user): bool
    {
        return $this->getCreator()->getId() === $user->getId();
    }

    /**
     * @Groups({"Default", "API"})
     */
    public function getCreatorProfile(): Profile
    {
        return $this->creator->getProfile();
    }

    /**
     * @Groups({"Default", "API"})
     */
    public function getUserVotings(): array
    {
        return $this->userVotings
            ? $this->userVotings->toArray()
            : []
            ;
    }

    public function addUserVoting(UserVoting $userVoting): self
    {
        $userVoting->setVoting($this);
        $this->userVotings->add($userVoting);

        return $this;
    }

    public function userVoted(User $user): bool
    {
        return count(array_filter(
            $this->getUserVotings(),
            static fn(UserVoting $userVoting) => $userVoting->getUser()->getId() === $user->getId()
        )) > 0;
    }

    /**
     * @Groups({"Default", "API"})
     */
    public function isClosed(): bool
    {
        return (new \DateTimeImmutable()) > $this->endDate;
    }
}
