<?php declare(strict_types = 1);

namespace App\Entity\AirdropCampaign;

use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\AirdropCampaign\AirdropParticipantRepository")
 * @ORM\Table(
 *     name="airdrop_participants",
 *     uniqueConstraints={@ORM\UniqueConstraint(name="airdrop_participant_index", columns={"user_id", "airdrop_id"})}
 * )
 * @codeCoverageIgnore
 */
class AirdropParticipant
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="user_id", nullable=false)
     * @var User
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\AirdropCampaign\Airdrop", inversedBy="claimedParticipants")
     * @ORM\JoinColumn(name="airdrop_id", nullable=false)
     * @var Airdrop
     */
    private $airdrop;

    public function getId(): int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getAirdrop(): ?Airdrop
    {
        return $this->airdrop;
    }

    public function setAirdrop(Airdrop $airdrop): self
    {
        $this->airdrop = $airdrop;

        return $this;
    }
}
