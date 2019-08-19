<?php declare(strict_types = 1);

namespace App\Entity;

use App\Entity\Token\Token;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Model\User as BaseUser;
use JMS\Serializer\Annotation as Serializer;
use Ramsey\Uuid\Uuid;
use Scheb\TwoFactorBundle\Model\BackupCodeInterface;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface as EmailTwoFactorInterface;
use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface;
use Scheb\TwoFactorBundle\Model\PreferredProviderInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="user")
 * @Serializer\ExclusionPolicy("all")
 * @Serializer\XmlRoot(name="_group")
 */
class User extends BaseUser implements
    TwoFactorInterface,
    EmailTwoFactorInterface,
    BackupCodeInterface,
    PreferredProviderInterface
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Groups({"sonata_api_read","sonata_api_write","sonata_search"})
     * @Serializer\Since(version="1.0")
     * @Serializer\Type(name="integer")
     * @Serializer\SerializedName("id")
     * @Serializer\XmlAttributeMap
     * @Serializer\Expose
     * @var int
     * @Groups({"API"})
     */
    protected $id;

    /**
     * @ORM\Column(type="string", nullable=true, unique=true)
     * @var string|null
     */
    protected $hash;

    /**
     * @ORM\Column(type="string", nullable=true, unique=true)
     * @var string|null
     */
    protected $referralCode;

    /** @var string */
    protected $username;

    /**
     * @Assert\NotBlank()
     * @Assert\Email(
     *     message = "Invalid email address.",
     *     checkMX = true,
     *     mode = "strict"
     * )
     * @var string
     */
    protected $email;

    /**
     * @Assert\Length(min="8", max="72")
     * @Assert\Regex(
     *     pattern="/(?=.*[\p{Lu}])(?=.*[\p{Ll}])(?=.*[\p{N}]).{8,}/",
     *     match=true,
     *     message="The password must contain minimum eight symbols,
     *     at least one uppercase letter, a lowercase letter, and a number"
     * )
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
     * @ORM\Column(type="string", nullable=true)
     * @var string|null
     */
    private $authCode;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     * @var \DateTimeImmutable
     */
    protected $authCodeExpirationTime;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Token\Token", inversedBy="relatedUsers")
     * @ORM\JoinTable(name="user_tokens",
     *     joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="token_id", referencedColumnName="id")}
     * )
     * @var ArrayCollection
     */
    protected $relatedTokens;

    /**
     * @ORM\OneToMany(targetEntity="User", mappedBy="referencer")
     * @var ArrayCollection
     */
    protected $referrals;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="referrals")
     * @ORM\JoinColumn(name="referencer_id", referencedColumnName="id", onDelete="SET NULL")
     * @var User|null
     */
    protected $referencer;

    /**
     * @ORM\OneToMany(targetEntity="PendingWithdraw", mappedBy="user")
     * @var ArrayCollection
     */
    protected $pendingWithdrawals;

    /** @codeCoverageIgnore */
    public function getPreferredTwoFactorProvider(): ?string
    {
        return 'email';
    }

    /**
     * @codeCoverageIgnore
     * @return Token[]
     */
    public function getRelatedTokens(): array
    {
        return $this->relatedTokens->toArray();
    }

    /** @codeCoverageIgnore */
    public function addRelatedToken(Token $token): self
    {
        $this->relatedTokens->add($token);

        return $this;
    }

    /** @codeCoverageIgnore */
    public function removeRelatedToken(Token $token): self
    {
        $this->relatedTokens->removeElement($token);

        return $this;
    }

    /**
     * @codeCoverageIgnore
     * @Groups({"API"})
     */
    public function getProfile(): ?Profile
    {
        return $this->profile;
    }

    /** @codeCoverageIgnore */
    public function setProfile(Profile $profile): self
    {
        $this->profile = $profile;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     * {@inheritdoc}
     */
    public function setEmail($email)
    {
        $this->username = $email;

        return parent::setEmail($email);
    }

    public function isGoogleAuthenticatorEnabled(): bool
    {
        return null !== $this->googleAuthenticatorEntry;
    }

    /** @codeCoverageIgnore */
    public function getGoogleAuthenticatorUsername(): string
    {
        return $this->username;
    }

    public function getGoogleAuthenticatorSecret(): string
    {
        $googleAuth = $this->googleAuthenticatorEntry;

        return null !== $googleAuth && null !== $googleAuth->getSecret()
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

    /** @codeCoverageIgnore */
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

    /** @codeCoverageIgnore */
    public function setGoogleAuthenticatorSecret(string $secret): void
    {
        $this->getGoogleAuthenticatorEntry()->setSecret($secret);
    }

    /** @codeCoverageIgnore */
    public function setGoogleAuthenticatorBackupCodes(array $codes): void
    {
        $this->getGoogleAuthenticatorEntry()->setBackupCodes($codes);
    }

    /** @codeCoverageIgnore */
    public function getHash(): ?string
    {
        return $this->hash;
    }

    /** @codeCoverageIgnore */
    public function setHash(?string $hash): self
    {
        $this->hash = $hash;

        return $this;
    }

    /** @codeCoverageIgnore */
    public function getReferrencer(): ?self
    {
        return $this->referencer;
    }

    /** @codeCoverageIgnore */
    public function setReferrencer(User $user): self
    {
        $this->referencer = $user;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     * @return User[]
     */
    public function getReferrals(): array
    {
        return $this->referrals->toArray();
    }

    /** @codeCoverageIgnore */
    public function getReferralCode(): string
    {
        return $this->referralCode ?? '';
    }

    /** @codeCoverageIgnore */
    public function getUsername(): string
    {
        return $this->username;
    }

    /** @codeCoverageIgnore */
    public function getTawkHash(string $api_key): string
    {
        return hash_hmac('sha256', $this->getUsername(), $api_key);
    }

    /**
     * @codeCoverageIgnore
     * @ORM\PrePersist()
     */
    public function prePersist(): void
    {
        $this->referralCode = Uuid::uuid1()->toString();
    }

    /** @codeCoverageIgnore */
    public function isEmailAuthEnabled(): bool
    {
        return !$this->isGoogleAuthenticatorEnabled();
    }

    /** @codeCoverageIgnore */
    public function getEmailAuthRecipient(): string
    {
        return $this->email;
    }

    /** @codeCoverageIgnore */
    public function getEmailAuthCode(): string
    {
        return $this->authCode ?? '';
    }

    /** @codeCoverageIgnore */
    public function setEmailAuthCode(string $authCode): void
    {
        $this->authCode = $authCode;
    }

    public function getEmailAuthCodeExpirationTime(): \DateTimeImmutable
    {
        return $this->authCodeExpirationTime;
    }

    public function setEmailAuthCodeExpirationTime(\DateTimeImmutable $authCodeExpirationTime): void
    {
        $this->authCodeExpirationTime = $authCodeExpirationTime;
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
