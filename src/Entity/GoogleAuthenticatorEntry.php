<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\GoogleAuthenticatorEntryRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class GoogleAuthenticatorEntry
{
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
     * @ORM\Column(type="json_array", nullable=true)
     * @var string[]|null
     */
    protected $backupCodes;
    

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
            if (false !== $key)
                unset($this->backupCodes[$key]);
        }
    }
}
