<?php declare(strict_types = 1);

namespace App\Entity;

use App\Entity\Token\Token;
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

    public function __construct(Money $reward, User $user)
    {
        $this->reward = $reward->getAmount();
        $this->user = $user;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getReward(): Money
    {
        return new Money($this->reward, new Currency(Token::WEB_SYMBOL));
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
