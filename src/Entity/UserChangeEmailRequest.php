<?php declare(strict_types = 1);

namespace App\Entity;

use App\Entity\ValidationCode\ChangeEmailValidationCode;
use App\Entity\ValidationCode\ValidationCodeInterface;
use App\Entity\ValidationCode\ValidationCodeOwner;
use App\Validator\Constraints as AppAssert;
use DateTimeImmutable;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=App\Repository\UserChangeEmailRequestRepository::class)
 * @ORM\HasLifecycleCallbacks()
 */
class UserChangeEmailRequest extends ValidationCodeOwner
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="userChangeEmailRequests")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected User $user;

    /**
     * @ORM\OneToMany(
     *     targetEntity="App\Entity\ValidationCode\ChangeEmailValidationCode",
     *     mappedBy="userChangeEmailRequest",
     *     indexBy="change_email_id",
     *     cascade={"persist", "remove"}
     * )
     */
    protected Collection $validationCode;

    /**
     * @ORM\Column(
     *     type="string",
     *     length=320,
     *     nullable=false,
     * )
     * @Assert\NotBlank()
     * @Assert\Email(
     *     message = "Invalid email address.",
     *     checkMX = true,
     *     mode = "strict"
     * )
     * @AppAssert\IsNotBlacklisted(type="email", message="This domain is not allowed")
     * @AppAssert\UserEmailSymbols()
     */
    protected string $oldEmail;


    /**
     * @ORM\Column(
     *     type="string",
     *     length=320,
     *     nullable=false,
     * )
     * @Assert\NotBlank()
     * @Assert\Email(
     *     message = "Invalid email address.",
     *     checkMX = true,
     *     mode = "strict"
     * )
     * @AppAssert\IsNotBlacklisted(type="email", message="This domain is not allowed")
     * @AppAssert\UserEmailSymbols()
     */
    protected string $newEmail;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private ?DateTimeImmutable $createdAt;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private ?DateTimeImmutable $confirmedAt;

    public function __construct(User $user, string $newEmail)
    {
        $this->user = $user;

        $this->oldEmail = $user->getEmail();
        $this->newEmail = $newEmail;

        parent::__construct();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getOldEmail(): ?string
    {
        return $this->oldEmail;
    }

    public function getNewEmail(): string
    {
        return $this->newEmail;
    }

    public function getCurrentEmailCode(): ?ValidationCodeInterface
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('codeType', ChangeEmailValidationCode::TYPE_CURRENT_EMAIL))
            ->setMaxResults(1);

        return $this->getValidationCode()->matching($criteria)->first() ?: null;
    }

    public function setCurrentEmailCode(ValidationCodeInterface $smsCode): self
    {
        $smsCode->setCodeType(ChangeEmailValidationCode::TYPE_CURRENT_EMAIL);
        $this->addValidationCode($smsCode);

        return $this;
    }

    public function getNewEmailCode(): ?ValidationCodeInterface
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('codeType', ChangeEmailValidationCode::TYPE_NEW_EMAIL))
            ->setMaxResults(1);

        return $this->getValidationCode()->matching($criteria)->first() ?: null;
    }

    public function setNewEmailCode(ValidationCodeInterface $smsCode): self
    {
        $smsCode->setCodeType(ChangeEmailValidationCode::TYPE_NEW_EMAIL);
        $this->addValidationCode($smsCode);

        return $this;
    }

    /**
     * @ORM\PrePersist
     */
    public function setCreatedAt(): self
    {
        $this->createdAt = new DateTimeImmutable();

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setConfirmedAt(): self
    {
        $this->confirmedAt = new DateTimeImmutable();

        return $this;
    }

    public function getConfirmedAt(): ?DateTimeImmutable
    {
        return $this->confirmedAt;
    }
}
