<?php declare(strict_types = 1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Unsubscriber
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(type="string", nullable=false)
     * @var string
     */
    protected $email;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=false)
     * @var \DateTimeImmutable
     */
    protected $date;

    public function __construct(string $email, \DateTimeImmutable $date)
    {
        $this->email = $email;
        $this->date = $date;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }
}
