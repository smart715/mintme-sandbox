<?php declare(strict_types = 1);

namespace App\Entity\AirdropCampaign;

use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * @codeCoverageIgnore
 * @ORM\Entity(repositoryClass="App\Repository\PostRepository")
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
     */
    protected User $user;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\AirdropCampaign\Airdrop", cascade={"remove"})
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
