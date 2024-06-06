<?php declare(strict_types = 1);

namespace App\EventSubscriber;

use App\Entity\Token\Token;
use App\Events\DepositCompletedEvent;
use App\Events\TransactionCompletedEvent;
use App\Events\TransactionDelayedEvent;
use App\Events\WithdrawCompletedEvent;
use App\Mailer\MailerInterface;
use App\Manager\UserNotificationManagerInterface;
use App\Notifications\Strategy\DepositNotificationStrategy;
use App\Notifications\Strategy\NotificationContext;
use App\Notifications\Strategy\TransactionDelayedNotificationStrategy;
use App\Notifications\Strategy\WithdrawalNotificationStrategy;
use App\Utils\NotificationChannels;
use App\Utils\NotificationTypes;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TransactionSubscriber implements EventSubscriberInterface
{
    private MailerInterface $mailer;
    private MoneyWrapperInterface $moneyWrapper;
    private LoggerInterface $logger;
    private EntityManagerInterface $em;
    private UserNotificationManagerInterface $userNotificationManager;

    public function __construct(
        MailerInterface $mailer,
        MoneyWrapperInterface $moneyWrapper,
        LoggerInterface $logger,
        EntityManagerInterface $em,
        UserNotificationManagerInterface $userNotificationManager
    ) {
        $this->mailer = $mailer;
        $this->moneyWrapper = $moneyWrapper;
        $this->logger = $logger;
        $this->em = $em;
        $this->userNotificationManager = $userNotificationManager;
    }

    public static function getSubscribedEvents(): array
    {
        return [
           DepositCompletedEvent::NAME => [
               ['sendTransactionCompletedMail'],
               ['updateTokenWithdraw'],
           ],
           WithdrawCompletedEvent::NAME => [
               ['sendTransactionCompletedMail'],
               ['updateTokenWithdraw'],
           ],
           TransactionDelayedEvent::NAME => [
               ['sendTransactionDelayedMail'],
           ],
        ];
    }

    public function sendTransactionCompletedMail(TransactionCompletedEvent $event): void
    {
        $user = $event->getUser();

        try {
            if ($event instanceof WithdrawCompletedEvent) {
                $notificationType = NotificationTypes::WITHDRAWAL;
                $strategy = new WithdrawalNotificationStrategy(
                    $this->userNotificationManager,
                    $notificationType
                );
                $notificationContext = new NotificationContext($strategy);
                $notificationContext->sendNotification($user);
            } else {
                $notificationType = NotificationTypes::DEPOSIT;
                $strategy = new DepositNotificationStrategy(
                    $this->userNotificationManager,
                    $notificationType
                );
                $notificationContext = new NotificationContext($strategy);
                $notificationContext->sendNotification($user);
            }

            $isAvailableEmailNotification = $this->userNotificationManager->isNotificationAvailable(
                $user,
                $notificationType,
                NotificationChannels::EMAIL
            );

            if ($isAvailableEmailNotification) {
                $this->mailer->sendTransactionCompletedMail(
                    $user,
                    $event->getTradable(),
                    $this->moneyWrapper->parse($event->getAmount(), $event->getTradable()->getMoneySymbol()),
                    $event->getAddress(),
                    $event::TYPE,
                    $event->getCryptoNetworkName()
                );
                $this->logger->info("Sent ".$event::TYPE." completed e-mail to user {$user->getEmail()}");
            }
        } catch (\Throwable $e) {
            $this->logger->error(
                "Couldn't send ".$event::TYPE
                ." completed e-mail to user {$user->getEmail()}. Reason: {$e->getMessage()}"
            );
        }
    }

    public function updateTokenWithdraw(TransactionCompletedEvent $event): void
    {
        $tradable = $event->getTradable();
        $user = $event->getUser();
        $amount = $this->moneyWrapper->parse($event->getAmount(), Symbols::TOK);

        if (!$tradable instanceof Token
            || $user->getId() !== $tradable->getProfile()->getUser()->getId()
            || $amount->isZero()
        ) {
            return;
        }

        $withdrawnObj = $tradable->getWithdrawn();

        if ($event instanceof DepositCompletedEvent) {
            $withdrawnObj = $withdrawnObj->subtract($amount);
        }

        if ($event instanceof WithdrawCompletedEvent) {
            $withdrawnObj = $withdrawnObj->add($amount);
        }

        try {
            $tradable->setWithdrawn($withdrawnObj->getAmount());
            $this->em->persist($tradable);
            $this->em->flush();
            $this->logger->info(
                "[transaction-subscriber] Success token update withdrawn operation.",
                [
                    'tokenName' => $tradable->getName(),
                    'tokenWithdrawn' => $tradable->getWithdrawn()->getAmount(),
                ]
            );
        } catch (\Throwable $exception) {
            $this->logger->error("[transaction-subscriber] Failed to update token withdrawn. Reason: {$exception->getMessage()}");
        }
    }

    public function sendTransactionDelayedMail(TransactionDelayedEvent $event): void
    {
        $user = $event->getReport()->getUser();

        $strategy = new TransactionDelayedNotificationStrategy(
            $this->userNotificationManager,
            $event->getType()
        );

        $notificationContext = new NotificationContext($strategy);
        $notificationContext->sendNotification($user);

        $isAvailableEmailNotification = $this->userNotificationManager->isNotificationAvailable(
            $user,
            $strategy->getParentNotificationType(),
            NotificationChannels::EMAIL
        );

        if ($isAvailableEmailNotification) {
            $this->mailer->sendTransactionDelayedMail($user);
        }
    }
}
