<?php declare(strict_types = 1);

namespace App\EventSubscriber;

use App\Entity\Token\Token;
use App\Events\DepositCompletedEvent;
use App\Events\TransactionCompletedEvent;
use App\Events\WithdrawCompletedEvent;
use App\Mailer\MailerInterface;
use App\Manager\UserNotificationManagerInterface;
use App\Notifications\Strategy\DepositNotificationStrategy;
use App\Notifications\Strategy\NotificationContext;
use App\Notifications\Strategy\WithdrawalNotificationStrategy;
use App\Utils\NotificationChannels;
use App\Utils\NotificationTypes;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currency;
use Money\Money;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TransactionSubscriber implements EventSubscriberInterface
{
    /** @var MailerInterface */
    private $mailer;

    /** @var MoneyWrapperInterface */
    private $moneyWrapper;

    /** @var LoggerInterface */
    private $logger;

    /** @var EntityManagerInterface */
    private $em;

    /** @var UserNotificationManagerInterface */
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
        ];
    }

    public function sendTransactionCompletedMail(TransactionCompletedEvent $event): void
    {
        $user = $event->getUser();

        try {
            $this->mailer->checkConnection();

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
                $this->mailer->sendTransactionCompletedMail($user, $event::TYPE);
                $this->logger->info("Sent ".$event::TYPE." completed e-mail to user {$user->getEmail()}");
            }
        } catch (\Throwable $e) {
            $this->logger->error("Couldn't send ".$event::TYPE." completed e-mail to user {$user->getEmail()}. Reason: {$e->getMessage()}");
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

        $withdrawnObj = new Money(
            $tradable->getWithdrawn(),
            new Currency(Symbols::TOK)
        );

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
                    'tokenWithdrawn' => $tradable->getWithdrawn(),
                ]
            );
        } catch (\Throwable $exception) {
            $this->logger->error("[transaction-subscriber] Failed to update token withdrawn. Reason: {$exception->getMessage()}");
        }
    }
}
