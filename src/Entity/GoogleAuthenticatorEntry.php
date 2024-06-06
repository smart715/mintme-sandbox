<?php declare(strict_types = 1);

namespace App\Entity;

use App\Entity\ValidationCode\SmsValidationCodeTrait;
use App\Entity\ValidationCode\ValidationCodeOwner;
use App\Entity\ValidationCode\ValidationCodeOwnerInterface;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;

/**
 * @ORM\Entity(repositoryClass="App\Repository\GoogleAuthenticatorEntryRepository")
 * @ORM\HasLifecycleCallbacks()
 * @codeCoverageIgnore
 */
class GoogleAuthenticatorEntry extends ValidationCodeOwner implements ValidationCodeOwnerInterface
{

    use SmsValidationCodeTrait;

    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int
     */
    protected $id;

    /**
     * @ORM\OneToOne(targetEntity="User", inversedBy="googleAuthenticatorEntry")
     * @var User
     */
    protected $user;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string|null
     */
    protected $secret;

    /**
     * @ORM\OneToMany(
     *     targetEntity="App\Entity\ValidationCode\BackupValidationCode",
     *     mappedBy="googleAuthenticatorEntry",
     *     indexBy="google_auth_entry_id",
     *     cascade={"persist", "remove"}
     * )
     * @var ArrayCollection|PersistentCollection
     */
    protected Collection $validationCode;

    /**
     * @ORM\Column(type="json_array", nullable=true)
     * @var string[]|null
     */
    protected $backupCodes;

    /**
     * @ORM\Column(type="integer", options={"default": 0})
     */
    private int $backupCodesDownloads = 0; // phpcs:ignore

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private ?DateTimeImmutable $lastdownloadBackupDate;

    public function getId(): int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getSecret(): ?string
    {
        return $this->secret;
    }

    public function setSecret(string $secret): void
    {
        $this->secret = $secret;
    }

    public function getBackupCodes(): array
    {
        return $this->backupCodes ?? [];
    }

    public function setBackupCodes(array $backupCodes): void
    {
        $this->backupCodes = $backupCodes;
    }

    public function invalidateBackupCode(string $code): void
    {
        if (null !== $this->backupCodes) {
            $key = array_search($code, $this->backupCodes);

            if (false !== $key) {
                unset($this->backupCodes[$key]);
            }
        }
    }

    public function getLastdownloadBackupDate(): ?DateTimeImmutable
    {
        return $this->lastdownloadBackupDate;
    }

    public function setLastdownloadBackupDate(?DateTimeImmutable $lastdownloadBackupDate): self
    {
        $this->lastdownloadBackupDate = $lastdownloadBackupDate;

        return $this;
    }

    public function getBackupCodesDownloads(): int
    {
        return $this->backupCodesDownloads;
    }

    public function setBackupCodesDownloads(int $backupCodesDownloads): self
    {
        $this->backupCodesDownloads = $backupCodesDownloads;

        return $this;
    }

    public function incrementBackupCodesDownloadCount(): void
    {
        $this->backupCodesDownloads++;
    }

    public function resetBackupCodesDownloadCount(): void
    {
        $this->setBackupCodesDownloads(0);
    }
}
