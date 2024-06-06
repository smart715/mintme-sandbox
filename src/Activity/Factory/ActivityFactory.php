<?php declare(strict_types = 1);

namespace App\Activity\Factory;

use App\Activity\ActivityHelper;
use App\Activity\ActivityTypes;
use App\Entity\Activity;
use App\Events\Activity\ActivityEventInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Activity factory using a factory method pattern
 * Every factory has an expected event interface and returns context base on it
 */
class ActivityFactory
{
    private ActivityHelper $activityHelper;
    private RouterInterface $router;
    private MoneyWrapperInterface $moneyWrapper;

    public function __construct(
        ActivityHelper $activityHelper,
        RouterInterface $router,
        MoneyWrapperInterface $moneyWrapper
    ) {
        $this->activityHelper = $activityHelper;
        $this->router = $router;
        $this->moneyWrapper = $moneyWrapper;
    }

    private const ACTIVITY_CREATORS = [
        ActivityTypes::DONATION => DonationActivityCreator::class,
        ActivityTypes::TOKEN_TRADED => OrderEventActivityCreator::class,
        ActivityTypes::MARKET_CREATED => MarketActivityCreator::class,
        ActivityTypes::COMMENT_LIKE => CommentLikeActivityCreator::class,
        ActivityTypes::SIGN_UP_BONUS_CREATED => SignUpBonusActivityCreator::class,
        ActivityTypes::TIP_RECEIVED => TipActivityCreator::class,
        ActivityTypes::SIGN_UP_CAMPAIGN => BonusActivityCreator::class,
        ActivityTypes::TOKEN_RELEASE_SET => TokenReleaseActivityCreator::class,
        ActivityTypes::TOKEN_CONNECTED => ConnectCompletedActivityCreator::class,
        ActivityTypes::TOKEN_ADDED => ConnectCompletedActivityCreator::class,

        ActivityTypes::AIRDROP_CLAIMED => AirdropClaimedActivityCreator::class,
        ActivityTypes::AIRDROP_REFERRAL => AirdropClaimedActivityCreator::class,

        ActivityTypes::USER_REGISTERED => UserActivityCreator::class,
        ActivityTypes::PHONE_VERIFIED => UserActivityCreator::class,

        ActivityTypes::PROPOSITION_ADDED => VotingActivityCreator::class,
        ActivityTypes::USER_VOTED => VotingActivityCreator::class,

        ActivityTypes::TOKEN_CREATED => TokenActivityCreator::class,
        ActivityTypes::AIRDROP_CREATED => TokenActivityCreator::class,
        ActivityTypes::AIRDROP_ENDED => TokenActivityCreator::class,
        ActivityTypes::TOKEN_DEPLOYED => TokenActivityCreator::class,
        ActivityTypes::TOKEN_NEW_DM => TokenActivityCreator::class,
        ActivityTypes::USER_FOLLOWED => TokenActivityCreator::class,
        ActivityTypes::PROJECT_DESCRIPTION_UPDATED => TokenActivityCreator::class,
        ActivityTypes::SOCIAL_MEDIA_UPDATED => TokenActivityCreator::class,
        ActivityTypes::DISCORD_REWARDS_ADDED => TokenActivityCreator::class,
        ActivityTypes::DISCORD_REWARD_RECEIVED => TokenActivityCreator::class,

        ActivityTypes::NEW_POST => PostEventActivityCreator::class,
        ActivityTypes::POST_COMMENTED => PostEventActivityCreator::class,
        ActivityTypes::POST_LIKE => PostEventActivityCreator::class,

        ActivityTypes::DEPOSITED => TransactionActivityCreator::class,
        ActivityTypes::WITHDRAWN => TransactionActivityCreator::class,

        ActivityTypes::REWARD_NEW => RewardActivityCreator::class,
        ActivityTypes::BOUNTY_NEW => RewardActivityCreator::class,
        ActivityTypes::REWARD_NEW_PARTICIPANT => RewardActivityCreator::class,
        ActivityTypes::REWARD_NEW_VOLUNTEER => RewardActivityCreator::class,
        ActivityTypes::BOUNTY_ACCEPTED => RewardActivityCreator::class,
        ActivityTypes::BOUNTY_PAID => RewardActivityCreator::class,
    ];

    public function create(ActivityEventInterface $event): Activity
    {
        $eventType = $event->getType();

        if (!isset(self::ACTIVITY_CREATORS[$eventType])) {
            throw new \AssertionError('Unsupported event type');
        }

        $creatorClass = self::ACTIVITY_CREATORS[$eventType];

        /** @var AbstractActivityCreator $creator */
        $creator = new $creatorClass($this->activityHelper, $this->router, $this->moneyWrapper);

        return $creator->create($event);
    }
}
