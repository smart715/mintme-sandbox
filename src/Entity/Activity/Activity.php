<?php declare(strict_types = 1);

namespace App\Entity\Activity;

use App\Entity\Token\Token;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @codeCoverageIgnore
 * @ORM\Entity(repositoryClass="App\Repository\ActivityRepository")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="integer")
 * @ORM\DiscriminatorMap({
 *     Activity::AIRDROP_CLAIMED = "AirdropClaimedActivity",
 *     Activity::AIRDROP_CREATED = "AirdropCreatedActivity",
 *     Activity::AIRDROP_ENDED = "AirdropEndedActivity",
 *     Activity::DONATION = "DonationActivity",
 *     Activity::NEW_POST = "NewPostActivity",
 *     Activity::TOKEN_CREATED = "TokenCreatedActivity",
 *     Activity::TOKEN_DEPLOYED = "TokenDeployedActivity",
 *     Activity::TOKEN_DEPOSITED = "TokenDepositedActivity",
 *     Activity::TOKEN_TRADED = "TokenTradedActivity",
 *     Activity::TOKEN_WITHDRAWN = "TokenWithdrawnActivity",
 * })
 * @ORM\HasLifecycleCallbacks()
 */
abstract class Activity
{
    public const AIRDROP_CLAIMED = 0;
    public const AIRDROP_CREATED = 1;
    public const AIRDROP_ENDED = 2;
    public const DONATION = 3;
    public const NEW_POST = 4;
    public const TOKEN_CREATED = 5;
    public const TOKEN_DEPLOYED = 6;
    public const TOKEN_DEPOSITED = 7;
    public const TOKEN_TRADED = 8;
    public const TOKEN_WITHDRAWN = 9;

    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected int $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Token\Token")
     * @ORM\JoinColumn(name="token_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected Token $token;

    /** @ORM\Column(type="datetime_immutable") */
    protected \DateTimeImmutable $createdAt;

    /** @Groups({"Default", "API"}) */
    abstract public function getType(): int;

    public function setToken(Token $token): self
    {
        $this->token = $token;

        return $this;
    }

    /** @Groups({"Default", "API"}) */
    public function getToken(): Token
    {
        return $this->token;
    }

    /** @ORM\PrePersist */
    public function setCreatedAt(): self
    {
        $this->createdAt = new \DateTimeImmutable();

        return $this;
    }

    /** @Groups({"Default", "API"}) */
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
