<?php

namespace App\Entity;

use App\Entity\Token\Token;
use App\Validator\Constraints\ProfilePeriodLock;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Intl\Intl;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ProfileRepository")
 * @ORM\HasLifecycleCallbacks()
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
     * @Assert\Regex(pattern="/^[^\d]+$/u")
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
     * @Assert\Regex(pattern="/^[^\d]+$/u")
     * @Assert\Length(min="2")
     * @Assert\Length(max="30")
     * @ProfilePeriodLock()
     * @Groups({"API", "Default"})
     * @var string|null
     */
    protected $lastName;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Regex(pattern="/^[^\d]+$/u")
     * @Assert\Length(min="2")
     * @Assert\Length(max="30")
     * @Groups({"Default"})
     * @var string|null
     */
    protected $city;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Country()
     * @Assert\Length(min="2")
     * @Assert\Length(max="30")
     * @Groups({"Default"})
     * @var string|null
     */
    protected $country;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max="150")
     * @Groups({"Default"})
     * @var string|null
     */
    protected $description;

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
     */
    protected $token;

    /** @var bool */
    private $isChangesLocked = false;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @var string|null
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
    public function updateNameChangedDate(): self
    {
        $this->nameChangedDate = new \DateTimeImmutable('+1 month');

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

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getCountry(): ?string
    {
        if($this->country)
            return Intl::getRegionBundle()->getCountryName($this->country);
        return $this->country;
    }

    public function setCountry(?string $country): self
    {
        $this->country = $country;

        return $this;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $this->removeDoubleSpaces($firstName);

        return $this;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $this->removeDoubleSpaces($lastName);

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

    protected function removeDoubleSpaces(string $text): ?string
    {
        return preg_replace('/\s+/', ' ', $text);
    }
}
