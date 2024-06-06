<?php declare(strict_types = 1);

namespace App\Entity;

use App\Entity\Token\Token;
use App\Utils\Symbols;
use Doctrine\ORM\Mapping as ORM;
use Money\Currency;
use Money\Money;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TopHolderRepository")
 * @ORM\Table(
 *     name="top_holders",
 *     uniqueConstraints={@ORM\UniqueConstraint(name="token_user_index", columns={"token_id", "user_id"})}
 * )
 * @codeCoverageIgnore
 */
class TopHolder
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected int $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Token\Token")
     * @ORM\JoinColumn(name="token_id", nullable=false, onDelete="CASCADE")
     */
    private Token $token;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="topHolders")
     * @ORM\JoinColumn(name="user_id", nullable=false, onDelete="CASCADE")
     */
    private User $user;

    /**
     * @ORM\Column(type="string")
     */
    private string $amount;

    /**
     * @ORM\Column(type="integer")
     */
    private int $rank;

    /**
     * @Groups({"API", "API_BASIC"})
     */
    public function getToken(): Token
    {
        return $this->token;
    }

    public function setToken(Token $token): self
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @Groups({"API", "API_BASIC"})
     */
    public function getAmount(): Money
    {
        return new Money($this->amount, new Currency(Symbols::TOK));
    }

    public function setAmount(string $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * @Groups({"API", "API_BASIC"})
     */
    public function getRank(): int
    {
        return $this->rank;
    }

    public function setRank(int $rank): self
    {
        $this->rank = $rank;

        return $this;
    }

    /**
     * @Groups({"API", "API_BASIC"})
     */
    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }
}
