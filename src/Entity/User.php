<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Model\User as BaseUser;
use Ramsey\Uuid\Uuid;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @UniqueEntity("email")
 */
class User extends BaseUser
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
     * @Assert\Regex(
     *     pattern="/(?=.*[\p{Lu}])(?=.*[\p{Ll}])(?=.*[\p{N}]).{8,}/",
     *     match=true,
     *     message="The password must contain minimum eight symbols,
           at least one uppercase letter, a lowercase letter, and a number"
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
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="referencedUsers")
     * @var User|null
     */
    private $referencer;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @var int|null
     */
    private $referencerId;

    /**
     * @ORM\Column(type="string", length=255)
     * @var string
     */
    private $referralCode;
    
    public function getId(): ?int
    {
        return $this->id;
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

    public function setProfile(Profile $profile): void
    {
        $this->profile = $profile;
    }

    /** {@inheritdoc} */
    public function setEmail($email)
    {
        $this->username = $email;
        return parent::setEmail($email);
    }
    
    public function getReferencerId(): ?int
    {
        return $this->referencerId;
    }

    public function setReferencerId(?int $referencerId): self
    {
        $this->referencerId = $referencerId;

        return $this;
    }

    public function getReferralCode(): ?string
    {
        if (empty($this->referralCode))
            $this->generateReferralCode();

        return $this->referralCode;
    }

    public function setReferralCode(string $referralCode): self
    {
        $this->referralCode = $referralCode;

        return $this;
    }
    
    private function generateReferralCode(): void
    {
        $this->referralCode = Uuid::uuid4()->toString();
    }
    
    public function referenceBy(User $user): void
    {
        $this->referencer = $user;
    }
    
    public function getReferencer(): ?User
    {
        return $this->referencer;
    }
}
