<?php declare(strict_types = 1);

namespace App\EventSubscriber;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Events\DepositCompletedEvent;
use App\Events\TransactionCompletedEvent;
use App\Events\WithdrawCompletedEvent;
use App\Mailer\MailerInterface;
use App\Manager\UserNotificationManagerInterface;
use App\Utils\NotificationChannels;
use App\Utils\NotificationTypes;
use App\Wallet\Money\MoneyWrapper;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currency;
use Money\Money;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
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

            $notificationType = $event instanceof DepositCompletedEvent
                ? NotificationTypes::DEPOSIT
                : NotificationTypes::WITHDRAWAL;

            $isAvailableEmailNotification = $this->userNotificationManager->isNotificationAvailable(
                $user,
                $notificationType,
                NotificationChannels::EMAIL
            );

            if ($isAvailableEmailNotification) {
                $this->mailer->sendTransactionCompletedMail($user, $notificationType);
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
        $amount = $this->moneyWrapper->parse($event->getAmount(), MoneyWrapper::TOK_SYMBOL);

        if (!$tradable instanceof Token
            || $user->getId() !== $tradable->getProfile()->getUser()->getId()
            || $amount->isZero()
        ) {
            return;
        }

        $withdrawnObj = new Money(
            $tradable->getWithdrawn(),
            new Currency(MoneyWrapper::TOK_SYMBOL)
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
