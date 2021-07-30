<?php declare(strict_types = 1);

namespace App\Entity\Voting;

use App\Entity\Token\Token;
use Doctrine\ORM\Mapping as ORM;

/**
 * @codeCoverageIgnore
 * @ORM\Entity()
 */
class TokenVoting extends Voting
{
    /**
     * @ORM\ManyToOne(
     *     targetEntity="App\Entity\Token\Token",
     *     inversedBy="votings",
     *     cascade={"persist", "remove"}
     * )
     * @ORM\JoinColumn(name="token_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private Token $token;

    public function getToken(): Token
    {
        return $this->token;
    }

    public function setToken(Token $token): self
    {
        $this->token = $token;

        return $this;
    }
}
