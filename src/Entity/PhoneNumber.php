<?php declare(strict_types = 1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

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
    private int $profile;

    /**
     * @ORM\Column(type="phone_number", unique=true)
     */
    private string $phoneNumber;

    /**
     * @ORM\Column(type="string", length=PhoneNumber::CODE_LENGTH, nullable=true)
     */
    private ?string $verificationCode;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $verified = false; // phpcs:ignore

    public function getProfile(): int
    {
        return $this->profile;
    }

    public function setProfile(int $profile): self
    {
        $this->profile = $profile;

        return $this;
    }

    public function getPhoneNumber(): string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(string $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    public function getVerificationCode(): ?string
    {
        return $this->verificationCode;
    }

    public function setVerificationCode(): self
    {
        // generate a fixed-length verification code that's zero-padded, e.g. 007828, 936504, 150222
        $this->verificationCode = sprintf(
            '%0'.self::CODE_LENGTH.'d',
            mt_rand(1, (int)str_repeat((string)9, self::CODE_LENGTH))
        );

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
