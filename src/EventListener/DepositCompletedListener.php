<?php declare(strict_types = 1);

namespace App\EventListener;

use App\Entity\Crypto;
use App\Events\DepositCompletedEvent;
use App\Mailer\MailerInterface;
use App\Wallet\Money\MoneyWrapper;
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

        $symbol = $tradable instanceof Crypto
            ? $tradable->getSymbol()
            : MoneyWrapper::TOK_SYMBOL;

        $amount = $this->moneyWrapper->format(
            $this->moneyWrapper->parse($event->getAmount(), $symbol)
        );

        $this->mailer->sendDepositCompletedMail($tradable, $user, $amount);
    }
}
