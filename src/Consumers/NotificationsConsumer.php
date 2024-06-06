<?php declare(strict_types = 1);

namespace App\Consumers;

use App\Consumers\Helpers\DBConnection;
use App\Entity\Crypto;
use App\Entity\TradableInterface;
use App\Entity\User;
use App\Events\TransactionDelayedEvent;
use App\Mailer\MailerInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Manager\UserManagerInterface;
use App\Manager\WrappedCryptoTokenManagerInterface;
use App\Wallet\Model\LackMainBalanceReport;
use App\Wallet\Model\MainBalanceCallbackMessage;
use App\Wallet\Model\Type;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class NotificationsConsumer implements ConsumerInterface
{
    private const NOT_ENOUGH_MAIN_BALANCE = 'not-enough-main-balance';
    private const BLOCKCHAIN_NODE_STATUS = 'blockchain-node-status';

    private MailerInterface $mailer;
    private LoggerInterface $logger;
    private EntityManagerInterface $em;
    private UserManagerInterface $userManager;
    private CryptoManagerInterface $cryptoManager;
    private TokenManagerInterface $tokenManager;
    private EventDispatcherInterface $eventDispatcher;
    private MoneyWrapperInterface $moneyWrapper;
    private WrappedCryptoTokenManagerInterface $wrappedCryptoTokenManager;

    /** @var string[] */
    private array $adminEmails;

    public function __construct(
        MailerInterface $mailer,
        LoggerInterface $logger,
        EntityManagerInterface $em,
        UserManagerInterface $userManager,
        CryptoManagerInterface $cryptoManager,
        TokenManagerInterface $tokenManager,
        EventDispatcherInterface $eventDispatcher,
        MoneyWrapperInterface $moneyWrapper,
        WrappedCryptoTokenManagerInterface $wrappedCryptoTokenManager,
        array $adminEmails
    ) {
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->em = $em;
        $this->userManager = $userManager;
        $this->cryptoManager = $cryptoManager;
        $this->tokenManager = $tokenManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->moneyWrapper = $moneyWrapper;
        $this->adminEmails = $adminEmails;
        $this->wrappedCryptoTokenManager = $wrappedCryptoTokenManager;
    }

    /** {@inheritdoc} */
    public function execute(AMQPMessage $msg)
    {
        if (!DBConnection::initConsumerEm(
            'email-consumer',
            $this->em,
            $this->logger
        )) {
            return false;
        }

        $this->em->clear();

        $this->logger->info('[notifications-consumer] Received new message: ' . json_encode($msg->body));

        try {
            $body = json_decode($msg->body, true);
            $type = $body['type'];

            if (self::NOT_ENOUGH_MAIN_BALANCE === $type) {
                $this->handleMainBalanceMsg($body['message']);

                return true;
            }

            if (self::BLOCKCHAIN_NODE_STATUS === $type) {
                $this->handleBlockchainNodeStatusMsg($body['message']);

                return true;
            }

            throw new \Exception('Not supported notification type');
        } catch (\Throwable $exception) {
            $this->logger->warning(
                '[notifications-consumer] Failed to handle incoming message: ' . $exception->getMessage(),
                [$msg->body, $exception->getTraceAsString()]
            );

            return false;
        }
    }

    private function handleMainBalanceMsg(array $message): void
    {
        $clbResult = MainBalanceCallbackMessage::parse($message);

        try {
            $user = $this->getUser($clbResult->getUserId());
            $tradable = $this->getTradable($clbResult->getCrypto(), $clbResult->getToken());
            $cryptoNetwork = $this->getCrypto($clbResult->getCrypto());
        } catch (\Throwable $exception) {
            $this->logger->warning(
                '[notifications-consumer] Failed to handle incoming message: ' . $exception->getMessage(),
                [$message]
            );

            return;
        }

        $wrappedCryptoToken = $tradable instanceof Crypto
            ? $this->wrappedCryptoTokenManager->findByCryptoAndDeploy($tradable, $cryptoNetwork)
            : null;
        $isNativeCryptoOnAnotherBlockchain = $wrappedCryptoToken && $wrappedCryptoToken->isNative();
        $tradableEqualsCryptoNetwork = $tradable->getSymbol() === $cryptoNetwork->getSymbol();

        $isToken = !$tradableEqualsCryptoNetwork && !$isNativeCryptoOnAnotherBlockchain
            || $tradableEqualsCryptoNetwork && $tradable instanceof Crypto && $tradable->isToken();

        $amount = $this->moneyWrapper->parse($clbResult->getAmount(), $tradable->getMoneySymbol());

        $tradableBalance = $this->moneyWrapper->parse(
            $isToken ? $clbResult->getTokenBalance() : $clbResult->getCryptoBalance(),
            $tradable->getMoneySymbol()
        );

        $tradableNeed = $this->moneyWrapper->parse(
            $isToken ? $clbResult->getTokenNeed() : $clbResult->getCryptoNeed(),
            $tradable->getMoneySymbol()
        );

        $networkBalance = $this->moneyWrapper->parse(
            $clbResult->getCryptoBalance(),
            $isNativeCryptoOnAnotherBlockchain
                ? $tradable->getMoneySymbol()
                : $cryptoNetwork->getMoneySymbol()
        );

        $networkNeed = $this->moneyWrapper->parse(
            $clbResult->getCryptoNeed(),
            $isNativeCryptoOnAnotherBlockchain
                ? $tradable->getMoneySymbol()
                : $cryptoNetwork->getMoneySymbol()
        );

        $type = Type::fromString($clbResult->getType());

        $wrappedCryptoToken = $this->wrappedCryptoTokenManager->findNativeBlockchainCrypto($cryptoNetwork);

        $nativeMoneyCrypto = $wrappedCryptoToken && $wrappedCryptoToken->isNative()
            ? $wrappedCryptoToken->getCrypto()
            : null;

        $report =  new LackMainBalanceReport(
            $user,
            $type,
            $amount,
            $tradable,
            $tradableBalance,
            $tradableNeed,
            $cryptoNetwork,
            $networkNeed,
            $networkBalance,
            $nativeMoneyCrypto,
            $isToken
        );

        try {
            $this->sendAdminNotification($report);

            $this->eventDispatcher->dispatch(
                new TransactionDelayedEvent($type, $report),
                TransactionDelayedEvent::NAME
            );
        } catch (\Throwable $exception) {
            $this->logger->warning(
                '[notifications-consumer] Failed to send notification: ' . $exception->getMessage(),
                [$message]
            );
        }
    }

    private function handleBlockchainNodeStatusMsg(array $message): void
    {
        if (!in_array($message['status'], [Crypto::BLOCKCHAIN_STATUS_OK, Crypto::BLOCKCHAIN_STATUS_FAILED])) {
            $this->logger->warning(
                '[notifications-consumer] Received new message with unknown blockchain status. Skipping',
                $message
            );

            return;
        }

        $crypto = $this->cryptoManager->findBySymbol($message['crypto']);

        if (!$crypto) {
            $this->logger->warning(
                '[notifications-consumer] Received new message with unknown or disabled crypto. Skipping',
                $message
            );

            return;
        }

        $crypto->setBlockchainStatus($message['status']);

        $this->em->persist($crypto);
        $this->em->flush();
    }

    private function getUser(int $id): User
    {
        /** @var User|null $user */
        $user = $this->userManager->find($id);

        if (!$user) {
            throw new \Exception('[notifications-consumer] Received new message with undefined user');
        }

        return $user;
    }

    private function getTradable(string $cryptoSymbol, ?string $token): TradableInterface
    {
        $crypto = $this->getCrypto($cryptoSymbol);

        if (!$token) {
            $nativeBlockchainCrypto = $this->wrappedCryptoTokenManager->findNativeBlockchainCrypto($crypto);

            return $nativeBlockchainCrypto
                ? $nativeBlockchainCrypto->getCrypto()
                : $crypto;
        }

        $tradable = $this->cryptoManager->findBySymbol($token)
            ?? $this->tokenManager->findByName($token);

        if (!$tradable) {
            throw new \Exception("[notifications-consumer] Asset '$token' was not found");
        }

        // in case it is USDC or WMM
        if ($tradable instanceof Crypto && !$tradable->canBeWithdrawnTo($crypto)) {
            throw new \Exception("[notifications-consumer] Crypto: $token does not work in $cryptoSymbol network");
        }

        return $tradable;
    }

    private function getCrypto(string $symbol): Crypto
    {
        $crypto = $this->cryptoManager->findBySymbol($symbol);

        if (!$crypto) {
            throw new \Exception("[notifications-consumer] Invalid crypto: $symbol given");
        }

        return $crypto;
    }

    private function sendAdminNotification(LackMainBalanceReport $report): void
    {
        foreach ($this->adminEmails as $email) {
            $this->mailer->sendLackBalanceReportMail($email, $report);

            $this->logger->info("[notifications-consumer] lack balance report sent to $email admin");
        }
    }
}
