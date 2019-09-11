<?php declare(strict_types = 1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Model\UserInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\BonusRepository")
 * @ORM\Table(name="bonus")
 * @ORM\HasLifecycleCallbacks()
 * @codeCoverageIgnore
 */
class Bonus
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
     * @var UserInterface
     */
    private $user;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $status;

    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    private $quantityWeb;

    public function __construct(UserInterface $user, string $status, int $quantityWeb)
    {
        $this->user = $user;
        $this->status = $status;
        $this->quantityWeb = $quantityWeb;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUser(): UserInterface
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getQuantityWeb(): int
    {
        return $this->quantityWeb;
    }

    public function setQuantityWeb(int $quantityWeb): void
    {
        $this->quantityWeb = $quantityWeb;
    }
}
