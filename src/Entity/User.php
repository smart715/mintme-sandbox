<?php

namespace App\Entity;

use App\Entity\Token\Token;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Model\User as BaseUser;
use Scheb\TwoFactorBundle\Model\BackupCodeInterface;
use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 */
class User extends BaseUser implements TwoFactorInterface, BackupCodeInterface
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int
     */
    protected $id;

    /** @var string */
    protected $username;

    /**
     * @Assert\NotBlank()
     * @Assert\Email()
     * @var string
     */
    protected $email;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string|null
     */
    protected $tempEmail;

    /**
     * @Assert\NotBlank()
     * @Assert\Length(min="8")
     * @var string|null
     */
    protected $plainPassword;

    /**
     * @ORM\OneToOne(targetEntity="Profile", mappedBy="user", cascade={"persist"})
     * @var Profile
     */
    protected $profile;
    
    /**
     * @ORM\OneToOne(targetEntity="GoogleAuthenticatorEntry", mappedBy="user", cascade={"persist"})
     * @var GoogleAuthenticatorEntry
     */
    protected $googleAuthenticatorEntry;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Token\Token")
     * @ORM\JoinTable(name="user_tokens",
     *     joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="token_id", referencedColumnName="id")}
     * )
     * @var ArrayCollection
     */
    protected $relatedTokens;

    /** @return Token[] */
    public function getRelatedTokens(): array
    {
        return $this->relatedTokens->toArray();
    }

    public function addRelatedToken(Token $token): self
    {
        $this->relatedTokens->add($token);

        return $this;
    }

    public function removeRelatedToken(Token $token): self
    {
        $this->relatedTokens->removeElement($token);

        return $this;
    }

    public function getProfile(): ?Profile
    {
        return $this->profile;
    }

    public function getTempEmail(): ?string
    {
        return $this->tempEmail;
    }

    public function setTempEmail(?string $email): self
    {
        $this->tempEmail = $email;

        return $this;
    }

    public function setProfile(Profile $profile): self
    {
        $this->profile = $profile;

        return $this;
    }

    /** {@inheritdoc} */
    public function setEmail($email)
    {
        $this->username = $email;
        return parent::setEmail($email);
    }
    
    public function isGoogleAuthenticatorEnabled(): bool
    {
        return null !== $this->googleAuthenticatorEntry;
    }

    public function getGoogleAuthenticatorUsername(): string
    {
        return $this->username;
    }

    public function getGoogleAuthenticatorSecret(): string
    {
        $googleAuth = $this->googleAuthenticatorEntry;

        return null !== $googleAuth && null !==  $googleAuth->getSecret()
            ? $googleAuth->getSecret()
            : '';
    }

    public function isBackupCode(string $code): bool
    {
        $googleAuth = $this->googleAuthenticatorEntry;
        return null !== $googleAuth
            ? in_array($code, $googleAuth->getBackupCodes())
            : false;
    }
    
    public function invalidateBackupCode(string $code): void
    {
        if (null !== $this->googleAuthenticatorEntry) {
            $this->googleAuthenticatorEntry->invalidateBackupCode($code);
        }
    }

    public function getGoogleAuthenticatorBackupCodes(): array
    {
        $googleAuth = $this->googleAuthenticatorEntry;
        return null !== $googleAuth ? $googleAuth->getBackupCodes() : [];
    }

    public function setGoogleAuthenticatorSecret(string $secret): void
    {
        $this->getGoogleAuthenticatorEntry()->setSecret($secret);
    }

    public function setGoogleAuthenticatorBackupCodes(array $codes): void
    {
        $this->getGoogleAuthenticatorEntry()->setBackupCodes($codes);
    }

    private function getGoogleAuthenticatorEntry(): GoogleAuthenticatorEntry
    {
        if (null === $this->googleAuthenticatorEntry) {
            $this->googleAuthenticatorEntry = new GoogleAuthenticatorEntry();
        } elseif (null !== $this->googleAuthenticatorEntry
            && $this !== $this->googleAuthenticatorEntry->getUser()
        ) {
            $this->googleAuthenticatorEntry->setUser($this);
        }
        
        return $this->googleAuthenticatorEntry;
    }
}
