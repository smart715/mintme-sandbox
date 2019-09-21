<?php declare(strict_types = 1);

namespace App\EventListener;

use App\Events\DepositCompletedEvent;
use App\Mailer\MailerInterface;
use App\Wallet\Money\MoneyWrapperInterface;

class DepositCompletedListener
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

    public function onDepositCompleted(DepositCompletedEvent $event): void
    {
        $tradable = $event->getTradable();

        $user = $event->getUser();

        $amount = $this->moneyWrapper->format(
            $this->moneyWrapper->parse(
                $event->getAmount(),
                $tradable->getSymbol()
            )
        );

        $this->mailer->sendDepositCompletedMail($tradable, $user, $amount);
    }
}
