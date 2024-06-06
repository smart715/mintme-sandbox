<?php declare(strict_types = 1);

namespace App\Entity;

use App\Entity\Token\Token;
use Doctrine\ORM\Mapping as ORM;
use Money\Money;
use Symfony\Component\Serializer\Annotation\Groups;

/** @codeCoverageIgnore */
abstract class PromotionHistory implements PromotionHistoryInterface
{
    public const AIRDROP = 'airdrop';
    public const BOUNTY = 'bounty';
    public const TOKEN_SHOP = 'token_shop';
    public const SHARE_POST = 'share_post';
    public const COMMENT_TIP = 'comment_tip';
    public const TOKEN_SIGNUP = 'token_signup';

    public const TOKEN_PROMOTION = 'token_promotion';
    public const TOKEN_DEPLOYMENT = 'token_deployment';
    public const TOKEN_CONNECTION = 'token_connection';
    public const TOKEN_RELEASE_ADDRESS = 'token_release_address';
    public const TOKEN_NEW_MARKET = 'token_new_market';

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    protected int $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="user_id", nullable=false))
     */
    protected User $user;

    /**
     * @ORM\Column(type="datetime_immutable", options={"default": "CURRENT_TIMESTAMP"})
     */
    protected \DateTimeImmutable $createdAt;

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @Groups({"Default", "API", "PROMOTION_HISTORY"})
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @Groups({"Default", "API", "PROMOTION_HISTORY"})
     */
    public function getCreatedAt(): int
    {
        return $this->createdAt->getTimestamp();
    }

    /** @Groups({"Default", "API", "PROMOTION_HISTORY"}) */
    abstract public function getToken(): Token;

    /** @Groups({"Default", "API", "PROMOTION_HISTORY"}) */
    abstract public function getAmount(): Money;

    /** @Groups({"Default", "API", "PROMOTION_HISTORY"}) */
    abstract public function getType(): string;
}
