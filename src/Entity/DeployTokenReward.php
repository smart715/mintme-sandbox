<?php declare(strict_types = 1);

namespace App\Entity;

use App\Entity\Token\Token;
use App\Utils\Symbols;
use Doctrine\ORM\Mapping as ORM;
use Money\Currency;
use Money\Money;

/**
 * @ORM\Entity(repositoryClass="App\Repository\DeployTokenRewardRepository")
 * @ORM\Table(name="deploy_token_reward")
 * @codeCoverageIgnore
 */
class DeployTokenReward
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=100)
     * @var string
     */
    private $reward;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="user_id", nullable=false)
     * @var User
     */
    private $user;

    public function __construct(User $user, Money $reward)
    {
        $this->user = $user;
        $this->reward = $reward->getAmount();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getReward(): Money
    {
        return new Money($this->reward, new Currency(Symbols::WEB));
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
