<?php declare(strict_types = 1);

namespace App\Entity;

use App\Entity\AirdropCampaign\Airdrop;
use App\Entity\AirdropCampaign\AirdropAction;
use App\Entity\Api\Client;
use App\Entity\Token\Token;
use App\Validator\Constraints as AppAssert;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Model\User as BaseUser;
use JMS\Serializer\Annotation as Serializer;
use Ramsey\Uuid\Uuid;
use Scheb\TwoFactorBundle\Model\BackupCodeInterface;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface as EmailTwoFactorInterface;
use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface;
use Scheb\TwoFactorBundle\Model\PreferredProviderInterface;
use Scheb\TwoFactorBundle\Model\TrustedDeviceInterface;
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
    PreferredProviderInterface,
    TrustedDeviceInterface
{
    public const ROLE_API = 'ROLE_API';

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
     * @Groups({"API", "Default"})
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
     * @AppAssert\IsNotBlacklisted(type="email", message="This domain is not allowed")
     * @AppAssert\UserEmailSymbols()
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
     * @ORM\OneToOne(targetEntity="Profile", mappedBy="user", cascade={"persist", "remove"})
     * @var Profile|null
     */
    protected $profile;

    /**
     * @ORM\OneToOne(targetEntity="GoogleAuthenticatorEntry", mappedBy="user", cascade={"persist", "remove"})
     * @var GoogleAuthenticatorEntry|null
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
     * @ORM\OneToMany(targetEntity="UserToken", mappedBy="user")
     * @var ArrayCollection
     */
    protected $tokens;

    /**
     * @ORM\OneToMany(targetEntity="UserCrypto", mappedBy="user", cascade={"persist", "remove"})
     * @var ArrayCollection
     */
    protected $cryptos;

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

    /**
     * @ORM\Column(type="integer", nullable=true, options={"default": 0})
     * @var int
     */
    protected $trustedTokenVersion = 0;

    /**
     * @ORM\OneToOne(targetEntity="ApiKey", mappedBy="user", cascade={"remove", "persist"})
     * @var ApiKey
     */
    protected $apiKey;


    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Api\Client", mappedBy="user", cascade={"remove", "persist"})
     * @var ArrayCollection
     */
    protected $clients;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Bonus")
     * @ORM\JoinColumn(name="bonus_id", referencedColumnName="id")
     * @var Bonus|null
     */
    protected $bonus;

    /**
     * @ORM\Column(type="boolean", nullable=false)
     * @var bool
     */
    protected $isBlocked = false;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Comment", mappedBy="author", fetch="EXTRA_LAZY")
     * @var ArrayCollection
     */
    protected $comments;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Comment", mappedBy="likes", fetch="EXTRA_LAZY")
     * @var ArrayCollection
     */
    protected $likes;

    /**
     * @ORM\Column(type="string", nullable=true, unique=true)
     * @var string
     */
    protected $coinifyOfflineToken;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\AirdropCampaign\AirdropAction")
     * @var ArrayCollection
     */
    protected $airdropActions;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $twitterAccessToken;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $twitterAccessTokenSecret;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Post", mappedBy="rewardedUsers")
     */
    protected Collection $rewardClaimedPosts;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="airdropReferrals")
     */
    protected ?User $airdropReferrerUser;

    /**
     * @ORM\OneToMany(targetEntity="User", mappedBy="airdropReferrerUser", fetch="EXTRA_LAZY")
     */
    protected Collection $airdropReferrals;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\AirdropCampaign\Airdrop")
     */
    protected ?Airdrop $airdropReferrer;

    /** @codeCoverageIgnore */
    public function getApiKey(): ?ApiKey
    {
        return $this->apiKey;
    }

    /** @codeCoverageIgnore
     * @return array
     */
    public function getApiClients(): array
    {
        return array_map(function (Client $client) {
            return ['id' => $client->getPublicId()];
        }, $this->clients->toArray());
    }


    /** @codeCoverageIgnore */
    public function getPreferredTwoFactorProvider(): ?string
    {
        return 'email';
    }

    /**
     * @codeCoverageIgnore
     * @return Token[]
     */
    public function getTokens(): array
    {
        return array_map(function (UserToken $userToken) {
            return $userToken->getToken();
        }, $this->tokens->toArray());
    }

    /** @codeCoverageIgnore */
    public function addToken(UserToken $userToken): self
    {
        $this->tokens->add($userToken);

        return $this;
    }

    /** @codeCoverageIgnore */
    public function addCrypto(UserCrypto $userCrypto): self
    {
        $this->cryptos->add($userCrypto);

        return $this;
    }

    /**
     * @codeCoverageIgnore
     * @Groups({"API", "Default"})
     */
    public function getProfile(): Profile
    {
        return $this->profile ?? new Profile($this);
    }

    /** @codeCoverageIgnore */
    public function setProfile(?Profile $profile): self
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
        $email = strtolower($email);
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

    public function getGoogleAuthenticatorSecret(): ?string
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
    public function getReferencer(): ?self
    {
        return $this->referencer;
    }

    /** @codeCoverageIgnore */
    public function setReferencer(User $user): self
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

    /**
     * @codeCoverageIgnore
     * @var string
     * @return string
     */
    public function getNickname(): string
    {
        return $this->getProfile()->getNickname();
    }

    /**
     * @codeCoverageIgnore
     * @param string $nickname
     * @return self
     */
    public function setNickname(?string $nickname): self
    {
        if (!$this->profile) {
            $this->profile = new Profile($this);
        }

        $this->profile->setNickname($nickname ?? '');

        return $this;
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

    /** @codeCoverageIgnore */
    public function getTrustedTokenVersion(): int
    {
        return $this->trustedTokenVersion;
    }

    /** @codeCoverageIgnore */
    public function setTrustedTokenVersion(int $trustedTokenVersion): self
    {
        $this->trustedTokenVersion = $trustedTokenVersion;

        return $this;
    }

    public function getBonus(): ?Bonus
    {
        return $this->bonus;
    }

    public function setBonus(?Bonus $bonus): void
    {
        $this->bonus= $bonus;
    }

    public function isBlocked(): bool
    {
        return $this->isBlocked;
    }

    public function setIsBlocked(bool $isBlocked): self
    {
        $this->isBlocked = $isBlocked;

        return $this;
    }

    public function getComments(): array
    {
        return $this->comments->toArray();
    }

    public function addComment(Comment $comment): self
    {
        $this->comments->add($comment);

        return $this;
    }

    public function getCoinifyOfflineToken(): ?string
    {
        return $this->coinifyOfflineToken;
    }

    public function setCoinifyOfflineToken(string $coinifyOfflineToken): self
    {
        $this->coinifyOfflineToken = $coinifyOfflineToken;

        return $this;
    }

    public function addAirdropAction(AirdropAction $action): self
    {
        $this->airdropActions->add($action);

        return $this;
    }

    public function getAirdropActions(): ArrayCollection
    {
        return $this->airdropActions;
    }

    public function setTwitterAccessToken(?string $token): self
    {
        $this->twitterAccessToken = $token;

        return $this;
    }

    public function getTwitterAccessToken(): ?string
    {
        return $this->twitterAccessToken;
    }

    public function setTwitterAccessTokenSecret(?string $token): self
    {
        $this->twitterAccessTokenSecret = $token;

        return $this;
    }

    public function getTwitterAccessTokenSecret(): ?string
    {
        return $this->twitterAccessTokenSecret;
    }

    /**
     * @Groups({"Default"})
     */
    public function isSignedInWithTwitter(): bool
    {
        return null !== $this->twitterAccessToken && null !== $this->twitterAccessTokenSecret;
    }

    public function setAirdropReferrer(Airdrop $airdrop): self
    {
        $this->airdropReferrer = $airdrop;

        return $this;
    }

    public function getAirdropReferrer(): ?Airdrop
    {
        return $this->airdropReferrer;
    }

    public function setAirdropReferrerUser(User $user): self
    {
        $this->airdropReferrerUser = $user;

        return $this;
    }

    public function getAirdropReferrerUser(): ?User
    {
        return $this->airdropReferrerUser;
    }
}
