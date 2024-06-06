<?php declare(strict_types = 1);

namespace App\Entity\Voting;

use App\Entity\User;
use App\Utils\Symbols;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Money\Currency;
use Money\Money;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserVotingRepository")
 * @ORM\Table(
 *     name="user_voting",
 *     uniqueConstraints={@UniqueConstraint(name="user_voting_index", columns={"user_id", "voting_id"})}
 *     )
 * @codeCoverageIgnore
 */
class UserVoting
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotNull
     */
    private string $amount = '0'; // phpcs:ignore

    /**
     * @ORM\Column(type="string")
     * @Assert\NotNull
     */
    private string $amountSymbol = Symbols::TOK; // phpcs:ignore

    /**
     * @ORM\ManyToOne(
     *     targetEntity="App\Entity\Voting\Voting",
     *     inversedBy="userVotings",
     *     cascade={"persist", "remove"}
     * )
     * @ORM\JoinColumn(name="voting_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private Voting $voting;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="App\Entity\User",
     *     cascade={"persist"}
     * )
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private User $user;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="App\Entity\Voting\Option",
     *     cascade={"persist", "remove"}
     * )
     * @ORM\JoinColumn(name="option_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private Option $option;

    public function getId(): int
    {
        return $this->id;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * @Groups({"Default", "API", "API_BASIC"})
     */
    public function getAmountMoney(): Money
    {
        return new Money($this->amount, new Currency($this->amountSymbol));
    }

    public function setAmount(string $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function setAmountSymbol(string $amountSymbol): self
    {
        $this->amountSymbol = $amountSymbol;

        return $this;
    }

    public function getVoting(): Voting
    {
        return $this->voting;
    }

    public function setVoting(Voting $voting): self
    {
        $this->voting = $voting;

        return $this;
    }

    /**
     * @Groups({"Default", "API", "API_BASIC"})
     */
    public function getOption(): Option
    {
        return $this->option;
    }

    public function setOption(Option $option): self
    {
        $this->option = $option;

        return $this;
    }

    /**
     * @Groups({"Default", "API", "API_BASIC"})
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
