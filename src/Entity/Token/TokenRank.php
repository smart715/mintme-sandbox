<?php declare(strict_types = 1);

namespace App\Entity\Token;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(readOnly=true)
 * @ORM\Table(name="view_token_rank")
 * @codeCoverageIgnore
 */
class TokenRank
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Token\Token", mappedBy="rank")
     */
    private Token $token;

    /**
     * @ORM\Column(type="integer")
     */
    private int $rank;

    public function getId(): int
    {
        return $this->id;
    }

    public function getToken(): Token
    {
        return $this->token;
    }

    public function getRank(): int
    {
        return $this->rank;
    }
}
