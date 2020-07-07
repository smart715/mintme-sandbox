<?php

namespace App\Entity;

use App\Entity\Token\Token;
use App\Repository\RewardDeployTokenRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=RewardDeployTokenRepository::class)
 */
class RewardDeployToken
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="float")
     */
    private $reward;

    /**
     * @ORM\OneToOne(targetEntity=Token::class, inversedBy="rewardDeploy", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $token_id;

    /**
     * @ORM\OneToOne(targetEntity=User::class, inversedBy="rewardDeploy", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $user_id;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReward(): ?float
    {
        return $this->reward;
    }

    public function setReward(float $reward): self
    {
        $this->reward = $reward;

        return $this;
    }

    public function getTokenId(): ?Token
    {
        return $this->token_id;
    }

    public function setTokenId(Token $token_id): self
    {
        $this->token_id = $token_id;

        return $this;
    }

    public function getUserId(): ?User
    {
        return $this->user_id;
    }

    public function setUserId(User $user_id): self
    {
        $this->user_id = $user_id;

        return $this;
    }
}
