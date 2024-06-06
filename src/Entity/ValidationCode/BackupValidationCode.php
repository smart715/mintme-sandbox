<?php declare(strict_types = 1);

namespace App\Entity\ValidationCode;

use App\Entity\GoogleAuthenticatorEntry;
use Doctrine\ORM\Mapping as ORM;

/**
 * @codeCoverageIgnore
 * @ORM\Entity()
 */
class BackupValidationCode extends ValidationCode
{
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\GoogleAuthenticatorEntry", inversedBy="validationCode")
     * @ORM\JoinColumn(name="google_auth_entry_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private GoogleAuthenticatorEntry $googleAuthenticatorEntry;

    public function setGoogleAuthEntry(GoogleAuthenticatorEntry $googleAuthEntry): void
    {
        $this->googleAuthenticatorEntry = $googleAuthEntry;
    }

    public function getOwner(): ValidationCodeOwnerInterface
    {
        return $this->googleAuthenticatorEntry;
    }
}
