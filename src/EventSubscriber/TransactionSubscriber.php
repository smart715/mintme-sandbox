<?php declare(strict_types = 1);

namespace App\EventSubscriber;

use App\Entity\Crypto;
use App\Events\DepositCompletedEvent;
use App\Events\TransactionCompletedEvent;
use App\Events\WithdrawCompletedEvent;
use App\Logger\UserActionLogger;
use App\Mailer\MailerInterface;
use App\Wallet\Money\MoneyWrapper;
use App\Wallet\Money\MoneyWrapperInterface;
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

    public function __construct(
        MailerInterface $mailer,
        MoneyWrapperInterface $moneyWrapper,
        LoggerInterface $logger
    ) {
        $this->mailer = $mailer;
        $this->moneyWrapper = $moneyWrapper;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
           DepositCompletedEvent::NAME => 'sendTransactionCompletedMail',
           WithdrawCompletedEvent::NAME => 'sendTransactionCompletedMail',
        ];
    }

    public function sendTransactionCompletedMail(TransactionCompletedEvent $event): void
    {
        $tradable = $event->getTradable();
        $user = $event->getUser();

        $symbol = $tradable instanceof Crypto
            ? $tradable->getSymbol()
            : MoneyWrapper::TOK_SYMBOL;

        $amount = $this->moneyWrapper->format(
            $this->moneyWrapper->parse($event->getAmount(), $symbol)
        );

        try {
            $this->mailer->sendTransactionCompletedMail($tradable, $user, $amount, $event::TYPE);
            $this->logger->info("Sent ".$event::TYPE." completed e-mail to user ".$user->getEmail());
        } catch (\Throwable $e) {
            $this->logger->error("Couldn't send ".$event::TYPE." completed e-mail to user ".$user->getEmail()."Reason: ".$e->getMessage());
        }
    }
}
