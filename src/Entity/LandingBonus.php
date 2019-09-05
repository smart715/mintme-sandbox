<?php declare(strict_types = 1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PendingWithdrawRepository")
 * @ORM\Table(name="landing_bonus")
 * @ORM\HasLifecycleCallbacks()
 * @codeCoverageIgnore
 */
class LandingBonus
{
    public const BONUS_WEB = 5;

    public const PENDING_STATUS = 'pending';

    public const PAID_STATUS = 'paid';

    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     * @var int
     */
    private $userId;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $status;

    public function getId(): int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }
}
