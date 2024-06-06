<?php declare(strict_types = 1);

namespace App\Entity;

use App\Entity\ValidationCode\MailValidationCodeTrait;
use App\Entity\ValidationCode\SmsValidationCodeTrait;
use App\Entity\ValidationCode\ValidationCodeOwner;
use App\Entity\ValidationCode\ValidationCodeOwnerInterface;
use App\Utils\RandomNumber;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumber as AssertPhoneNumber;

/**
 * @codeCoverageIgnore
 * @ORM\Entity(repositoryClass="App\Repository\PhoneNumberRepository")
 * @ORM\Table(name="phone_number")
 * @ORM\HasLifecycleCallbacks()
 */
class PhoneNumber extends ValidationCodeOwner implements ValidationCodeOwnerInterface
{
    
    use SmsValidationCodeTrait;
    use MailValidationCodeTrait;

    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Profile", inversedBy="phoneNumber")
     */
    private Profile $profile;

    /**
     * @ORM\Column(type="phone_number", unique=false)
     * @AssertPhoneNumber(type="mobile")
     */
    private \libphonenumber\PhoneNumber $phoneNumber;

    /**
     * @ORM\Column(type="phone_number", unique=false, nullable=true)
     * @AssertPhoneNumber(type="mobile")
     */
    private ?\libphonenumber\PhoneNumber $temPhoneNumber;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $verified = false; // phpcs:ignore

    /**
     * @ORM\Column(type="integer")
     */
    private int $failedAttempts = 0; // phpcs:ignore

    /**
     * @ORM\Column(type="integer")
     */
    private int $editAttempts = 0; // phpcs:ignore

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private ?DateTimeImmutable $editDate = null; // phpcs:ignore

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $provider = null; // phpcs:ignore

    /**
     * @ORM\OneToMany(
     *     targetEntity="App\Entity\ValidationCode\ValidationCode",
     *     mappedBy="phoneNumber",
     *     indexBy="phone_number_id",
     *     cascade={"persist", "remove"}
     * )
     * @var ArrayCollection|PersistentCollection
     */
    protected Collection $validationCode;

    public function getProfile(): Profile
    {
        return $this->profile;
    }

    public function setProfile(Profile $profile): self
    {
        $this->profile = $profile;

        return $this;
    }

    public function getPhoneNumber(): \libphonenumber\PhoneNumber
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(\libphonenumber\PhoneNumber $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    public function getTemPhoneNumber(): ?\libphonenumber\PhoneNumber
    {
        return $this->temPhoneNumber;
    }

    public function setTemPhoneNumber(?\libphonenumber\PhoneNumber $temPhoneNumber): self
    {
        $this->temPhoneNumber = $temPhoneNumber;

        return $this;
    }

    public function isVerified(): bool
    {
        return $this->verified;
    }

    public function setVerified(bool $verified): self
    {
        $this->verified = $verified;

        return $this;
    }

    public function getFailedAttempts(): int
    {
        return $this->failedAttempts;
    }

    public function incrementFailedAttempts(): self
    {
        $this->failedAttempts++;

        return $this;
    }

    public function setFailedAttempts(int $failedAttempts): self
    {
        $this->failedAttempts = $failedAttempts;

        return $this;
    }

    public function getEditAttempts(): int
    {
        return $this->editAttempts;
    }

    public function setEditAttempts(int $editAttempts): self
    {
        $this->editAttempts = $editAttempts;

        return $this;
    }

    public function getEditDate(): ?DateTimeImmutable
    {
        return $this->editDate;
    }

    public function setEditDate(?DateTimeImmutable $editDate): self
    {
        $this->editDate = $editDate;

        return $this;
    }

    public function getProvider(): ?string
    {
        return $this->provider;
    }

    public function setProvider(?string $provider): self
    {
        $this->provider = $provider;

        return $this;
    }
}
