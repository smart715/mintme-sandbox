<?php declare(strict_types = 1);

namespace App\EventSubscriber;

use App\Entity\Crypto;
use App\Events\DepositCompletedEvent;
use App\Events\TransactionCompletedEvent;
use App\Events\WithdrawCompletedEvent;
use App\Mailer\MailerInterface;
use App\Wallet\Money\MoneyWrapper;
use App\Wallet\Money\MoneyWrapperInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TransactionSubscriber implements EventSubscriberInterface
{
    /** @var MailerInterface */
    private $mailer;

    /** @var MoneyWrapperInterface */
    private $moneyWrapper;

    public function __construct(MailerInterface $mailer, MoneyWrapperInterface $moneyWrapper)
    {
        $this->mailer = $mailer;
        $this->moneyWrapper = $moneyWrapper;
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

        $this->mailer->sendTransactionCompletedMail($tradable, $user, $amount, $event::TYPE);
    }
}
