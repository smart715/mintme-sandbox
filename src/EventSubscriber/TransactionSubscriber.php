<?php declare(strict_types = 1);

namespace App\EventSubscriber;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Events\DepositCompletedEvent;
use App\Events\TransactionCompletedEvent;
use App\Events\WithdrawCompletedEvent;
use App\Mailer\MailerInterface;
use App\Manager\ProfileManager;
use App\Manager\ProfileManagerInterface;
use App\Utils\Facebook\FacebookPixelCommunicator;
use App\Utils\Facebook\FacebookPixelCommunicatorInterface;
use App\Wallet\Money\MoneyWrapper;
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
    
    /** @var ProfileManagerInterface */
    private $profileManager;
    
    /** @var FacebookPixelCommunicatorInterface */
    private $facebookPixelCommunicator;

    public function __construct(
        MailerInterface $mailer,
        MoneyWrapperInterface $moneyWrapper,
        LoggerInterface $logger,
        EntityManagerInterface $em,
        ProfileManagerInterface $profileManager,
        FacebookPixelCommunicatorInterface $facebookPixelCommunicator
    ) {
        $this->mailer = $mailer;
        $this->moneyWrapper = $moneyWrapper;
        $this->logger = $logger;
        $this->em = $em;
        $this->profileManager = $profileManager;
        $this->facebookPixelCommunicator = $facebookPixelCommunicator;
    }

    public static function getSubscribedEvents(): array
    {
        return [
           DepositCompletedEvent::NAME => [
               ['sendTransactionCompletedMail'],
               ['updateTokenWithdraw'],
               ['sendFacebookEvent'],
           ],
           WithdrawCompletedEvent::NAME => [
               ['sendTransactionCompletedMail'],
               ['updateTokenWithdraw'],
               ['sendFacebookEvent'],
           ],
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

        // Remove unneeded zeros and check how much decimals we need
        $subunit = strlen(
            rtrim(
                str_replace('.', '', (string)strstr($amount, '.')),
                '0'
            )
        );

        $amount = number_format((float)$amount, $subunit, '.', ',');

        try {
            $this->mailer->checkConnection();
            $this->mailer->sendTransactionCompletedMail($tradable, $user, $amount, $event::TYPE);
            $this->logger->info("Sent ".$event::TYPE." completed e-mail to user {$user->getEmail()}");
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
    
    public function sendFacebookEvent(TransactionCompletedEvent $event): void
    {
        if (DepositCompletedEvent::TYPE == $event::TYPE) {
            $this->sendFacebookDepositEvent($event);
        } elseif (WithdrawCompletedEvent::TYPE == $event::TYPE) {
            $this->sendFacebookWithdrawEvent($event);
        }
    }
    
    private function sendFacebookDepositEvent(TransactionCompletedEvent $event): void
    {
        $this->facebookPixelCommunicator->sendEvent(
            'Deposit',
            $event->getUser()->getEmail(),
            [
                'amount' => $event->getAmount(),
                'currency' => $event->getTradable()->getSymbol(),
            ],
            $this->profileManager->getProfile($event->getUser())
        );
    }
    
    private function sendFacebookWithdrawEvent(TransactionCompletedEvent $event): void
    {
        $this->facebookPixelCommunicator->sendEvent(
            'Withdraw',
            $event->getUser()->getEmail(),
            [
                'amount' => $event->getAmount(),
                'currency' => $event->getTradable()->getSymbol(),
            ],
            $this->profileManager->getProfile($event->getUser())
        );
    }
}
