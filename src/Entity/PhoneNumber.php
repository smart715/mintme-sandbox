<?php declare(strict_types = 1);

namespace App\Entity;

use App\Utils\RandomNumber;
use Doctrine\ORM\Mapping as ORM;
use Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumber as AssertPhoneNumber;

/**
 * @codeCoverageIgnore
 * @ORM\Entity(repositoryClass="App\Repository\PhoneNumberRepository")
 * @ORM\Table(name="phone_number")
 * @ORM\HasLifecycleCallbacks()
 */
class PhoneNumber
{
    public const CODE_LENGTH = 6;

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
     * @ORM\Column(type="phone_number", unique=true)
     * @AssertPhoneNumber(type="mobile")
     */
    private \libphonenumber\PhoneNumber $phoneNumber;

    /**
     * @ORM\Column(type="string", length=RandomNumber::CODE_LENGTH, nullable=true)
     */
    private ?string $verificationCode;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $verified = false; // phpcs:ignore

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

    public function getVerificationCode(): ?string
    {
        return $this->verificationCode;
    }

    public function setVerificationCode(?string $code): self
    {
        $this->verificationCode = $code;

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
}
