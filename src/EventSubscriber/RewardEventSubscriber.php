<?php declare(strict_types = 1);

namespace App\EventSubscriber;

use App\Entity\Rewards\RewardParticipant;
use App\Entity\User;
use App\Entity\UserTokenFollow;
use App\Events\RewardEvent;
use App\Mailer\MailerInterface;
use App\Manager\UserNotificationManagerInterface;
use App\Manager\UserTokenFollowManagerInterface;
use App\Notifications\Strategy\NotificationContext;
use App\Notifications\Strategy\RewardNewNotificationStrategy;
use App\Notifications\Strategy\RewardNewVolunteerNotificationStrategy;
use App\Notifications\Strategy\RewardParticipantDeliveredNotificationStrategy;
use App\Notifications\Strategy\RewardParticipantNotificationStrategy;
use App\Notifications\Strategy\RewardParticipantRefundNotificationStrategy;
use App\Notifications\Strategy\RewardParticipantRejectedNotificationStrategy;
use App\Notifications\Strategy\RewardVolunteerAcceptedNotificationStrategy;
use App\Notifications\Strategy\RewardVolunteerCompletedNotificationStrategy;
use App\Notifications\Strategy\RewardVolunteerRejectedNotificationStrategy;
use App\Repository\RewardRepository;
use App\Utils\NotificationTypes;
use App\Utils\Policy\NotificationPolicyInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RewardEventSubscriber implements EventSubscriberInterface
{
    private MailerInterface $mailer;
    private UserNotificationManagerInterface $userNotificationManager;
    private NotificationPolicyInterface $notificationPolicy;
    private MoneyWrapperInterface $moneyWrapper;
    private RewardRepository $rewardRepository;
    private UserTokenFollowManagerInterface $userTokenFollowManager;

    public function __construct(
        UserNotificationManagerInterface $userNotificationManager,
        MailerInterface $mailer,
        NotificationPolicyInterface $notificationPolicy,
        MoneyWrapperInterface $moneyWrapper,
        RewardRepository $rewardRepository,
        UserTokenFollowManagerInterface $userTokenFollowManager
    ) {
        $this->mailer = $mailer;
        $this->userNotificationManager = $userNotificationManager;
        $this->notificationPolicy = $notificationPolicy;
        $this->moneyWrapper = $moneyWrapper;
        $this->rewardRepository = $rewardRepository;
        $this->userTokenFollowManager = $userTokenFollowManager;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RewardEvent::PARTICIPANT_ADDED => 'onParticipantAdded',
            RewardEvent::VOLUNTEER_NEW => 'onVolunteerNew',
            RewardEvent::VOLUNTEER_ACCEPTED => 'onVolunteerAccepted',
            RewardEvent::VOLUNTEER_COMPLETED => 'onVolunteerCompleted',
            RewardEvent::VOLUNTEER_REJECTED => 'onVolunteerRejected',
            RewardEvent::REWARD_DELETED => 'onRewardDeleted',
            RewardEvent::PARTICIPANT_REJECTED => 'onParticipantRejected',
            RewardEvent::PARTICIPANT_DELIVERED => 'onParticipantDelivered',
            RewardEvent::PARTICIPANT_REFUNDED => 'onParticipantRefund',
            RewardEvent::REWARD_NEW => 'onRewardNew',
        ];
    }

    public function onRewardNew(RewardEvent $event): void
    {
        $reward = $event->getReward();
        $token = $reward->getToken();

        /**@var User $rewardCreator*/

        $notificationType = $reward->isBountyType()
            ? NotificationTypes::BOUNTY_NEW
            : NotificationTypes::REWARD_NEW;

        $strategy = new RewardNewNotificationStrategy(
            $reward,
            $this->userNotificationManager,
            $this->mailer,
            $notificationType,
            $this->rewardRepository
        );

        $notificationContext = new NotificationContext($strategy);
        $followers = $this->userTokenFollowManager->getFollowers($token);

        foreach ($followers as $follower) {
            if ($this->notificationPolicy->canReceiveNotification($follower, $token)) {
                $notificationContext->sendNotification($follower);
            }
        }
    }

    public function onParticipantAdded(RewardEvent $event): void
    {
        $reward = $event->getReward();
        $notificationType = NotificationTypes::REWARD_PARTICIPANT;

        $strategy = new RewardParticipantNotificationStrategy(
            $reward,
            $this->userNotificationManager,
            $this->mailer,
            $notificationType,
        );

        $notificationContext = new NotificationContext($strategy);
        $notificationContext->sendNotification($reward->getToken()->getOwner());
    }

    public function onVolunteerNew(RewardEvent $event): void
    {
        $reward = $event->getReward();
        $notificationType = NotificationTypes::REWARD_VOLUNTEER_NEW;
        $strategy = new RewardNewVolunteerNotificationStrategy(
            $reward,
            $this->userNotificationManager,
            $this->mailer,
            $notificationType,
        );

        $notificationContext = new NotificationContext($strategy);
        $notificationContext->sendNotification($reward->getToken()->getOwner());
    }

    public function onVolunteerAccepted(RewardEvent $event): void
    {
        $reward = $event->getReward();
        $token = $reward->getToken();
        $volunteer = $event->getRewardMember()->getUser();

        $notificationType = NotificationTypes::REWARD_VOLUNTEER_ACCEPTED;

        $strategy = new RewardVolunteerAcceptedNotificationStrategy(
            $reward,
            $token,
            $this->userNotificationManager,
            $this->mailer,
            $notificationType,
        );

        $notificationContext = new NotificationContext($strategy);
        $notificationContext->sendNotification($volunteer);
    }

    public function onVolunteerCompleted(RewardEvent $event): void
    {
        $reward = $event->getReward();
        $token = $reward->getToken();
        $volunteer = $event->getRewardMember()->getUser();

        $notificationType = NotificationTypes::REWARD_VOLUNTEER_COMPLETED;

        $strategy = new RewardVolunteerCompletedNotificationStrategy(
            $reward,
            $token,
            $this->userNotificationManager,
            $this->mailer,
            $notificationType,
        );

        $notificationContext = new NotificationContext($strategy);
        $notificationContext->sendNotification($volunteer);
    }

    public function onVolunteerRejected(RewardEvent $event): void
    {
        $reward = $event->getReward();
        $token = $reward->getToken();
        $volunteer = $event->getRewardMember()->getUser();

        $notificationType = NotificationTypes::REWARD_VOLUNTEER_REJECTED;

        $strategy = new RewardVolunteerRejectedNotificationStrategy(
            $reward,
            $token,
            $this->userNotificationManager,
            $this->mailer,
            $notificationType,
        );

        $notificationContext = new NotificationContext($strategy);
        $notificationContext->sendNotification($volunteer);
    }

    public function onRewardDeleted(RewardEvent $event): void
    {
        $reward = $event->getReward();
        $token = $reward->getToken();
        $volunteers = $event->getRewardMembers();

        $notificationType = NotificationTypes::REWARD_VOLUNTEER_REJECTED;

        $strategy = new RewardVolunteerRejectedNotificationStrategy(
            $reward,
            $token,
            $this->userNotificationManager,
            $this->mailer,
            $notificationType,
        );

        $notificationContext = new NotificationContext($strategy);

        foreach ($volunteers as $volunteer) {
            $notificationContext->sendNotification($volunteer->getUser());
        }
    }

    public function onParticipantRejected(RewardEvent $event): void
    {
        $reward = $event->getReward();
        $token = $reward->getToken();
        $volunteer = $event->getRewardMember()->getUser();

        $notificationType = NotificationTypes::REWARD_PARTICIPANT_REJECTED;

        $strategy = new RewardParticipantRejectedNotificationStrategy(
            $reward,
            $token,
            $this->userNotificationManager,
            $this->mailer,
            $notificationType,
        );

        $notificationContext = new NotificationContext($strategy);
        $notificationContext->sendNotification($volunteer);
    }

    public function onParticipantRefund(RewardEvent $event): void
    {
        $reward = $event->getReward();
        $token = $reward->getToken();

        /** @var RewardParticipant $participant*/
        $participant = $event->getRewardMember();

        $notificationType = NotificationTypes::REWARD_PARTICIPANT_REFUNDED;

        $strategy = new RewardParticipantRefundNotificationStrategy(
            $reward,
            $participant,
            $token,
            $this->mailer,
            $this->moneyWrapper,
            $this->userNotificationManager,
            $notificationType
        );

        $notificationContext = new NotificationContext($strategy);
        $notificationContext->sendNotification($participant->getUser());
    }

    public function onParticipantDelivered(RewardEvent $event): void
    {
        $reward = $event->getReward();
        $token = $reward->getToken();
        $volunteer = $event->getRewardMember()->getUser();

        $notificationType = NotificationTypes::REWARD_PARTICIPANT_DELIVERED;

        $strategy = new RewardParticipantDeliveredNotificationStrategy(
            $reward,
            $token,
            $this->userNotificationManager,
            $this->mailer,
            $notificationType,
        );

        $notificationContext = new NotificationContext($strategy);
        $notificationContext->sendNotification($volunteer);
    }
}
