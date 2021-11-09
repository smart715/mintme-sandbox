<?php declare(strict_types = 1);

namespace App\Entity\Voting;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use Doctrine\ORM\Mapping as ORM;

/**
 * @codeCoverageIgnore
 * @ORM\Entity()
 */
class CryptoVoting extends Voting
{
    /**
     * @ORM\ManyToOne(
     *     targetEntity="App\Entity\Crypto",
     *     inversedBy="votings",
     *     cascade={"persist", "remove"}
     * )
     * @ORM\JoinColumn(name="crypto_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private Crypto $crypto;

    public function getCrypto(): Crypto
    {
        return $this->crypto;
    }

    public function setCrypto(Crypto $crypto): self
    {
        $this->crypto = $crypto;

        return $this;
    }
}
