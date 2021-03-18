<?php declare(strict_types = 1);

namespace App\Entity;

use App\Utils\RandomNumber;
use DateTimeImmutable;
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
     * @ORM\Column(type="phone_number", unique=false)
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

    /**
     * @ORM\Column(type="integer")
     */
    private int $failedAttempts = 0; // phpcs:ignore

    /**
     * @ORM\Column(type="integer")
     */
    private int $dailyAttempts = 0; // phpcs:ignore

    /**
     * @ORM\Column(type="integer")
     */
    private int $weeklyAttempts = 0; // phpcs:ignore

    /**
     * @ORM\Column(type="integer")
     */
    private int $monthlyAttempts = 0; // phpcs:ignore

    /**
     * @ORM\Column(type="integer")
     */
    private int $totalAttempts = 0; // phpcs:ignore

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private ?DateTimeImmutable $attemptsDate = null; // phpcs:ignore

    /**
     * @ORM\Column(type="integer")
     */
    private int $editAttempts = 0; // phpcs:ignore

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private ?DateTimeImmutable $editDate = null; // phpcs:ignore

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

    public function getDailyAttempts(): int
    {
        return $this->dailyAttempts;
    }

    public function setDailyAttempts(int $dailyAttempts): self
    {
        $this->dailyAttempts = $dailyAttempts;

        return $this;
    }

    public function getWeeklyAttempts(): int
    {
        return $this->weeklyAttempts;
    }

    public function setWeeklyAttempts(int $weeklyAttempts): self
    {
        $this->weeklyAttempts = $weeklyAttempts;

        return $this;
    }

    public function getMonthlyAttempts(): int
    {
        return $this->monthlyAttempts;
    }

    public function setMonthlyAttempts(int $monthlyAttempts): self
    {
        $this->monthlyAttempts = $monthlyAttempts;

        return $this;
    }

    public function getTotalAttempts(): int
    {
        return $this->totalAttempts;
    }

    public function setTotalAttempts(int $totalAttempts): self
    {
        $this->totalAttempts = $totalAttempts;

        return $this;
    }

    public function getAttemptsDate(): ?DateTimeImmutable
    {
        return $this->attemptsDate;
    }

    public function setAttemptsDate(?DateTimeImmutable $attemptsDate = null): self
    {
        $this->attemptsDate = $attemptsDate ?? new DateTimeImmutable();

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
}
