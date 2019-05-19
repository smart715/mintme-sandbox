<?php declare(strict_types = 1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(uniqueConstraints={
 *     @ORM\UniqueConstraint(name="assignment_unique", columns={"type", "value"})
 * })
 * @ORM\Entity(repositoryClass="App\Repository\BlacklistRepository")
 */
class Blacklist
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
    protected $type;

    /**
     * @ORM\Column(type="string", nullable=false)
     * @var string
     */
    protected $value;

    public function __construct(string $value, string $type)
    {
        $this->value = $value;
        $this->type = $type;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
