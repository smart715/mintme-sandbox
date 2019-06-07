<?php declare(strict_types = 1);

namespace App\Entity;

use App\Entity\Token\Token;
use App\Validator\Constraints\ProfilePeriodLock;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Intl\Intl;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ProfileRepository")
 * @ORM\HasLifecycleCallbacks()
 * @codeCoverageIgnore
 */
class Profile
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\NotBlank()
     * @Assert\Regex(pattern="/^[A-Za-zÁ-Źá-ź]+[A-Za-zÁ-Źá-ź\s'‘’`´-]*$/u")
     * @Assert\Length(min="2")
     * @Assert\Length(max="30")
     * @ProfilePeriodLock()
     * @Groups({"API", "Default"})
     * @var string|null
     */
    protected $firstName;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\NotBlank()
     * @Assert\Regex(pattern="/^[A-Za-zÁ-Źá-ź]+[A-Za-zÁ-Źá-ź\s'‘’`´-]*$/u")
     * @Assert\Length(min="2")
     * @Assert\Length(max="30")
     * @ProfilePeriodLock()
     * @Groups({"API", "Default"})
     * @var string|null
     */
    protected $lastName;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Regex(pattern="/^[A-Za-zÁ-Źá-ź\s-]+$/u")
     * @Assert\Length(min="2")
     * @Assert\Length(max="30")
     * @Groups({"Default", "API"})
     * @var string|null
     */
    protected $city;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Country()
     * @Assert\Length(min="2")
     * @Assert\Length(max="30")
     * @Groups({"Default", "API"})
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

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     * @var \DateTimeImmutable|null
     */
    protected $nameChangedDate;

    /**
     * @ORM\OneToOne(targetEntity="User", inversedBy="profile", orphanRemoval=true)
     * @var User
     */
    protected $user;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Token\Token", mappedBy="profile", cascade={"persist", "remove"})
     * @var Token|null
     * @Groups({"API"})
     */
    protected $token;

    /** @var bool */
    private $isChangesLocked = false;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @var string|null
     * @Groups({"API"})
     */
    private $page_url;

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
        if ($args->hasChangedField('firstName') || $args->hasChangedField('lastName')) {
            $this->nameChangedDate = new \DateTimeImmutable('+1 month');
        }

        return $this;
    }

    public function getNameChangedDate(): ?\DateTimeImmutable
    {
        return $this->nameChangedDate;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function getDescription(): ?string
    {
        return $this->description;
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

    public function getCity(): ?string
    {
        return $this->city;
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

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): self
    {
        $this->country = $country;

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

    public function getPageUrl(): ?string
    {
        return $this->page_url;
    }

    public function setPageUrl(?string $page_url): self
    {
        $this->page_url = $page_url;

        return $this;
    }

    public function getUserEmail(): string
    {
        return $this->user->getEmail();
    }

    public function getToken(): ?Token
    {
        return $this->token;
    }
}
