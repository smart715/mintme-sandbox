<?php declare(strict_types = 1);

namespace App\Entity\ValidationCode;

use App\Entity\PhoneNumber;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * @codeCoverageIgnore
 * @ORM\Entity(repositoryClass="App\Repository\ValidationCodeRepository")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({
 *     "validationCode"="ValidationCode",
 *     "defaultValidationCode"="DefaultValidationCode",
 *     "backupValidationCode"="BackupValidationCode",
 *     "changeEmailValidationCode"="ChangeEmailValidationCode"
 * })
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="validation_code", indexes={@ORM\Index(name="FK_DCC410DF39DFD528", columns={"phone_number_id"})})
 */
class ValidationCode implements ValidationCodeInterface
{
    public const TYPE_SMS = 'sms';
    public const TYPE_MAIL = 'mail';

    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     * @ORM\Column(type="string", length=6, nullable=true)
     */
    private ?string $code;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $codeType;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private ?DateTimeImmutable $sendDate = null; // phpcs:ignore

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
     * @ORM\ManyToOne(targetEntity="App\Entity\PhoneNumber", inversedBy="validationCode")
     * @ORM\JoinColumn(name="phone_number_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    private ?PhoneNumber $phoneNumber;

    public function __construct(?PhoneNumber $phoneNumber)
    {
        $this->phoneNumber = $phoneNumber;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getCodeType(): string
    {
        return $this->codeType;
    }

    public function setCodeType(?string $codeType): self
    {
        $this->codeType = $codeType;

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

    public function getSendDate(): ?DateTimeImmutable
    {
        return $this->sendDate;
    }

    public function setSendDate(?DateTimeImmutable $sendDate): self
    {
        $this->sendDate = $sendDate;

        return $this;
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

    public function getOwner(): ?ValidationCodeOwnerInterface
    {
        return $this->phoneNumber;
    }

    public function getUser(): ?User
    {
        return $this->phoneNumber
            ? $this->phoneNumber
                ->getProfile()
                ->getUser()
            : null;
    }

    public function shouldBlockOnLimitReached(): bool
    {
        return true;
    }
}
