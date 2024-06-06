<?php declare(strict_types = 1);

namespace App\Entity\ValidationCode;

use App\Entity\UserChangeEmailRequest;
use Doctrine\ORM\Mapping as ORM;

/**
 * @codeCoverageIgnore
 * @ORM\Entity()
 */
class ChangeEmailValidationCode extends ValidationCode
{
    public const TYPE_CURRENT_EMAIL = 'current';
    public const TYPE_NEW_EMAIL = 'new';
    
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\UserChangeEmailRequest", inversedBy="validationCode")
     * @ORM\JoinColumn(name="user_change_email_request_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private UserChangeEmailRequest $userChangeEmailRequest;

    public function getOwner(): ValidationCodeOwnerInterface
    {
        return $this->userChangeEmailRequest;
    }

    public function setChangeEmail(UserChangeEmailRequest $userChangeEmailRequest): self
    {
        $this->userChangeEmailRequest = $userChangeEmailRequest;

        return $this;
    }

    public function shouldBlockOnLimitReached(): bool
    {
        return false;
    }
}
