<?php declare(strict_types = 1);

namespace App\Entity;

use App\Entity\Token\Token;
use App\Validator\Constraints as AppAssert;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Intl\Intl;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ProfileRepository")
 * @ORM\HasLifecycleCallbacks()
 * @codeCoverageIgnore
 */
class Profile implements ImagineInterface
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(type="string", unique=true, nullable=true)
     * @Groups({"API", "Default"})
     * @var string
     */
    protected $nickname;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @AppAssert\ProfileNameRequired()
     * @Assert\Regex(pattern="/^[\p{L}]+[\p{L}\s'‘’`´-]*$/u")
     * @Assert\Length(max="30")
     * @AppAssert\ProfilePeriodLock()
     * @var string|null
     */
    protected $firstName;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @AppAssert\ProfileNameRequired()
     * @Assert\Regex(pattern="/^[\p{L}]+[\p{L}\s'‘’`´-]*$/u")
     * @Assert\Length(max="30")
     * @AppAssert\ProfilePeriodLock()
     * @var string|null
     */
    protected $lastName;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Regex(pattern="/^[\p{L}\s-]+$/u")
     * @Assert\Length(min="2")
     * @Assert\Length(max="30")
     * @var string|null
     */
    protected $city;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Country()
     * @Assert\Length(min="2")
     * @Assert\Length(max="30")
     * @var string|null
     */
    protected $country;

    /**
     * @ORM\Column(type="string", length=500, nullable=true)
     * @Assert\Length(max="500")
     * @Groups({"Default", "API"})
     * @var string|null
     */
    protected $description;

    /**
     * @ORM\Column(type="boolean")
     * @Groups({"Default", "API"})
     * @var bool
     */
    protected $anonymous = false;

    public bool $disabledAnonymous = false;  // phpcs:ignore

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     * @var DateTimeImmutable|null
     */
    protected $nameChangedDate;

    /**
     * @ORM\OneToOne(targetEntity="User", inversedBy="profile", orphanRemoval=true)
     * @var User
     */
    protected $user;

    /**
     * @ORM\OneToOne(
     *     targetEntity="App\Entity\PhoneNumber",
     *     mappedBy="profile",
     *     orphanRemoval=true,
     *     cascade={"persist", "remove"}
     *     )
     * @ORM\JoinColumn(name="phone_number_id", referencedColumnName="id")
     */
    protected ?PhoneNumber $phoneNumber;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Token\Token", mappedBy="profile", cascade={"persist", "remove"})
     * @var ArrayCollection|null
     * @Groups({"API"})
     */
    protected $tokens;

    /** @var bool */
    private $isChangesLocked = false;

    /**
     * @ORM\Column(type="string", length=30, nullable=true)
     * @AppAssert\ZipCode(getter="getCountry")
     * @var string|null
     */
    protected $zipCode;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Image", cascade={"remove"}, orphanRemoval=true)
     * @ORM\JoinColumn(name="image_id", referencedColumnName="id")
     * @var Image|null
     */
    protected $image;

    /**
     * @ORM\Column(name="number_of_reminder", type="smallint")
     * @var int
     */
    private $numberOfReminder = 0;

    /**
     * @ORM\Column(name="next_reminder_date", type="date", nullable=true)
     * @var \DateTime
     */
    private $nextReminderDate;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     * @var DateTimeImmutable
     */
    private ?DateTimeImmutable $created;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function isChangesLocked(): bool
    {
        return $this->isChangesLocked;
    }

    public function lockChanges(): self
    {
        $this->isChangesLocked = true;

        return $this;
    }

    /** @ORM\PreUpdate() */
    public function updateNameChangedDate(PreUpdateEventArgs $args): self
    {
        if ($this->keyChanged($args, 'firstName') || $this->keyChanged($args, 'lastName')) {
            $this->nameChangedDate = new \DateTimeImmutable('+1 month');
        }

        return $this;
    }

    public function getNameChangedDate(): ?\DateTimeImmutable
    {
        return $this->nameChangedDate;
    }

    private function returnDefault(): bool
    {
        return !$this->isAnonymous() || $this->disabledAnonymous;
    }

    private function filterAnonymous(?string $property): string
    {
        return  is_null($property)
            ? ''
            : ($property && $this->returnDefault()
            ? $property
            : 'Anonymous');
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getNickname(): string
    {
        return $this->nickname ?? '';
    }

    /**
     * @Groups({"API", "Default"})
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->filterAnonymous($this->firstName);
    }

    /**
     * @return string
     * @Groups({"API", "Default"})
     */
    public function getLastName(): string
    {
        return $this->filterAnonymous($this->lastName);
    }

    /**
     * @Groups({"API", "Default"})
     */
    public function getDescription(): ?string
    {
        return $this->description && $this->returnDefault()
            ? $this->description
            : '';
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function isAnonymous(): bool
    {
        return $this->anonymous;
    }

    public function setAnonymous(bool $anonymous): self
    {
        $this->anonymous = $anonymous;

        return $this;
    }

    public function setDisabledAnonymous(bool $disabledAnonymous): self
    {
        $this->disabledAnonymous = $disabledAnonymous;

        return $this;
    }

    /**
     * @return string
     * @Groups({"API", "Default"})
     */
    public function getCity(): string
    {
        return $this->filterAnonymous($this->city);
    }

    public function setCity(?string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getCountryFullName(): ?string
    {
        if ($this->country) {
            return Intl::getRegionBundle()->getCountryName($this->country);
        }

        return null;
    }

    /**
     * @return string
     * @Groups({"API", "Default"})
     */
    public function getCountry(): string
    {
        return $this->filterAnonymous($this->country);
    }

    public function setCountry(?string $country): self
    {
        $this->country = $country;

        return $this;
    }

    public function setNickname(string $nickname): self
    {
        $this->nickname = $nickname;

        return $this;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getUserEmail(): string
    {
        return $this->user->getEmail();
    }

    public function getMintmeToken(): ?Token
    {
        /** @var Token $token */
        foreach ($this->getTokens() as $token) {
            if ($token->isMintmeToken()) {
                return $token;
            }
        }

        return null;
    }

    public function getFirstToken(): ?Token
    {
        if ($this->hasTokens()) {
            return $this->tokens[0];
        }

        return null;
    }

    public function getTokens(): array
    {
        return null !== $this->tokens
            ? $this->tokens->toArray()
            : [];
    }

    public function hasTokens(): bool
    {
        return count($this->getTokens()) > 0;
    }

    public function hasBlockedTokens(): bool
    {
        /** @var Token $token */
        foreach ($this->getTokens() as $token) {
            if ($token->isBlocked()) {
                return true;
            }
        }

        return false;
    }

    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    public function setZipCode(?string $zipCode = null): self
    {
        $this->zipCode = $zipCode;

        return $this;
    }

    private function keyChanged(PreUpdateEventArgs $args, string $name): bool
    {
        return $args->hasChangedField($name)
            && null !== $args->getOldValue($name)
            && ($args->getOldValue($name) || $args->getNewValue($name));
    }

    public function setImage(Image $image): void
    {
        $this->image = $image;
    }


    /**
     * @return Image
     * @Groups({"API", "Default"})
     */
    public function getImage(): Image
    {
        return $this->image && $this->returnDefault()
            ? $this->image
            : Image::defaultImage(Image::DEFAULT_PROFILE_IMAGE_URL);
    }


    /**
    * @Assert\Callback
    */
    public function validateNames(ExecutionContextInterface $context, ?string $payload): void
    {
        if (preg_match("/[A-Za-zÄÖÜäöüß -]/", strval($this->getFirstName()))) {
            if (2 > strlen(strval($this->getFirstName()))) {
                $context->buildViolation('This value is too short. It should have 2 characters or more.')
                ->atPath('firstName')
                ->addViolation();
            }
        }

        if (preg_match("/[A-Za-zÄÖÜäöüß -]/", strval($this->getLastName()))) {
            if (2 > strlen(strval($this->getLastName()))) {
                $context->buildViolation('This value is too short. It should have 2 characters or more.')
                ->atPath('lastName')
                ->addViolation();
            }
        }
    }

    public function getNumberOfReminder(): ?int
    {
        return $this->numberOfReminder;
    }

    public function setNumberOfReminder(int $numberOfReminder): self
    {
        $this->numberOfReminder = $numberOfReminder;

        return $this;
    }

    public function getNextReminderDate(): ?\DateTime
    {
        return $this->nextReminderDate;
    }

    public function setNextReminderDate(\DateTime $nextReminderDate): self
    {
        $this->nextReminderDate = $nextReminderDate;

        return $this;
    }

    /**
     * @ORM\PrePersist
     */
    public function setCreated(): self
    {
        $this->created = new DateTimeImmutable();

        return $this;
    }

    public function getCreated(): ?DateTimeImmutable
    {
        return $this->created;
    }

    public function getPhoneNumber(): ?PhoneNumber
    {
        return $this->phoneNumber;
    }


    public function setPhoneNumber(?PhoneNumber $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }
}
