<?php declare(strict_types = 1);

namespace App\Consumers;

use App\Communications\AMQP\MarketAMQPInterface;
use App\Consumers\Helpers\DBConnection;
use App\Exchange\Market;
use App\Manager\MarketStatusManagerInterface;
use App\Utils\Converter\MarketNameConverterInterface;
use Doctrine\ORM\EntityManagerInterface;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Throwable;

class MarketConsumer implements ConsumerInterface
{
    /** @var LoggerInterface */
    private $logger;

    /** @var MarketStatusManagerInterface */
    private $statusManager;

    /** @var MarketNameConverterInterface */
    private $marketNameConverter;

    /** @var MarketAMQPInterface */
    private $marketProducer;

    /** @var EntityManagerInterface */
    private $em;

    public function __construct(
        LoggerInterface $logger,
        MarketStatusManagerInterface $statusManager,
        MarketNameConverterInterface $marketNameConverter,
        MarketAMQPInterface $marketProducer,
        EntityManagerInterface $em
    ) {
        $this->logger = $logger;
        $this->statusManager = $statusManager;
        $this->marketNameConverter = $marketNameConverter;
        $this->marketProducer = $marketProducer;
        $this->em = $em;
    }

    public function execute(AMQPMessage $msg): bool
    {
        DBConnection::reconnectIfDisconnected($this->em);

        try {
            /** @var ?Market $market */
            $market = unserialize($msg->body);
        } catch (Throwable $exception) {
            $market = null;
        }

        if (!$market || !($market instanceof Market)) {
            $this->logger->warning('[market-consumer] Can not parse a message: '.$msg->getBody());

            return true;
        }

        $this->logger->info(
            '[market-consumer] Received a market updation message for '. $this->marketNameConverter->convert($market)
        );

        try {
            $this->statusManager->updateMarketStatus($market);
        } catch (Throwable $exception) {
            $this->logger->error(
                '[market-consumer] Can not update the market. Trying again. Reason: '.$exception->getMessage()
            );
            $this->marketProducer->send($market);
        }

        return true;
    }
}
