<?php declare(strict_types = 1);

namespace App\EventListener;

use App\Entity\Crypto;
use App\Events\WithdrawCompletedEvent;
use App\Mailer\MailerInterface;
use App\Wallet\Money\MoneyWrapperInterface;

class WithdrawCompletedListener
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

    public function onWithdrawCompleted(WithdrawCompletedEvent $event): void
    {
        $tradable = $event->getTradable();

        $user = $event->getUser();

        $symbol = $tradable instanceof Crypto ? $tradable->getSymbol() : $this->moneyWrapper::TOK_SYMBOL;

        $amount = $this->moneyWrapper->format(
            $this->moneyWrapper->parse($event->getAmount(), $symbol)
        );

        $this->mailer->sendWithdrawCompletedMail($tradable, $user, $amount);
    }
}
