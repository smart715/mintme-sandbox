<?php declare(strict_types = 1);

namespace App\Consumers;

use App\Communications\AMQP\MarketAMQPInterface;
use App\Consumers\Helpers\DBConnection;
use App\Entity\Token\Token;
use App\Exchange\Market;
use App\Manager\CryptoManagerInterface;
use App\Manager\MarketStatusManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Manager\TopHolderManagerInterface;
use App\Manager\UserManagerInterface;
use App\Utils\LockFactory;
use App\Wallet\Model\MarketCallbackMessage;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Throwable;

class MarketConsumer implements ConsumerInterface
{
    private const NUMBER_OF_RETRIES = 5;

    private LoggerInterface $logger;
    private MarketStatusManagerInterface $statusManager;
    private CryptoManagerInterface $cryptoManager;
    private TokenManagerInterface $tokenManager;
    private MarketAMQPInterface $marketProducer;
    private EntityManagerInterface $em;
    private LockFactory $lockFactory;
    private TopHolderManagerInterface $topHolderManager;
    private UserManagerInterface $userManager;

    public function __construct(
        LoggerInterface $logger,
        MarketStatusManagerInterface $statusManager,
        CryptoManagerInterface $cryptoManager,
        TokenManagerInterface $tokenManager,
        MarketAMQPInterface $marketProducer,
        EntityManagerInterface $em,
        LockFactory $lockFactory,
        TopHolderManagerInterface $topHolderManager,
        UserManagerInterface $userManager
    ) {
        $this->logger = $logger;
        $this->statusManager = $statusManager;
        $this->cryptoManager = $cryptoManager;
        $this->tokenManager = $tokenManager;
        $this->marketProducer = $marketProducer;
        $this->em = $em;
        $this->lockFactory = $lockFactory;
        $this->topHolderManager = $topHolderManager;
        $this->userManager = $userManager;
    }

    public function execute(AMQPMessage $msg): bool
    {
        if (!DBConnection::initConsumerEm(
            'market-consumer',
            $this->em,
            $this->logger
        )) {
            return false;
        }

        /** @var string $body */
        $body = $msg->body;

        $this->logger->info("[market-consumer] Received new message: {$body}");

        try {
            $clbResult = MarketCallbackMessage::parse(json_decode($body, true));
        } catch (Throwable $exception) {
            $this->logger->warning("[market-consumer] Failed to parse incoming message", [$msg->body]);

            return true;
        }

        $this->em->clear();

        $base = $this->cryptoManager->findBySymbol($clbResult->getBase())
            ?? $this->tokenManager->findByName($clbResult->getBase());
        $quote = $this->cryptoManager->findBySymbol($clbResult->getQuote())
            ?? $this->tokenManager->findByName($clbResult->getQuote());

        if (!$base || !$quote) {
            $this->logger->warning(
                '[market-consumer] base or quote not found: '.$body
            );

            return true;
        }

        $lock = $this->lockFactory->createLock("market-consumer-{$base->getSymbol()}-{$quote->getSymbol()}");

        if (!$lock->acquire()) {
            $this->logger->info("[market-consumer] Lock couldn't be acquired, skipping...");

            return true;
        }

        $market = new Market($base, $quote);
        $user = $clbResult->getUserId()
            ? $this->userManager->find($clbResult->getUserId())
            : null;

        try {
            $this->statusManager->updateMarketStatus($market);
        } catch (Throwable $exception) {
            $this->logger->error(
                '[market-consumer] Can not update the market. Trying again. Reason: '.$exception->getMessage()
            );

            if (!$exception instanceof InvalidArgumentException
                && $clbResult->getRetried() < self::NUMBER_OF_RETRIES) {
                $this->marketProducer->send($market, $user, $clbResult->incrementRetries());
            }
        }

        if ($quote instanceof Token && $user && $this->topHolderManager->shouldUpdateTopHolders($user, $quote)) {
            try {
                $this->topHolderManager->updateTopHolders($quote);
            } catch (\Throwable $exception) {
                $this->logger->error(
                    "[market-consumer] Can not update TopHolders. Reason: {$exception->getMessage()}"
                );
            }
        }

        $lock->release();

        return true;
    }
}
