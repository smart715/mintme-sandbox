<?php declare(strict_types = 1);

namespace App\Entity\AirdropCampaign;

use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * @codeCoverageIgnore
 * @ORM\Entity()
 * @ORM\Table(
 *     name="airdrop_referral_code",
 *     indexes={
 *         @ORM\Index(name="FK_AirdropReferralCode_User", columns={"user_id"}),
 *         @ORM\Index(name="FK_AirdropReferralCode_Airdrops", columns={"airdrop_id"})
 *    },
 * )
 */
class AirdropReferralCode
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected int $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", cascade={"remove"})
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected User $user;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\AirdropCampaign\Airdrop", cascade={"remove"})
     * @ORM\JoinColumn(name="airdrop_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected Airdrop $airdrop;

    public function getId(): int
    {
        return $this->id;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setAirdrop(Airdrop $airdrop): self
    {
        $this->airdrop = $airdrop;

        return $this;
    }

    public function getAirdrop(): Airdrop
    {
        return $this->airdrop;
    }
}
