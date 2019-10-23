<?php declare(strict_types = 1);

namespace App\Consumers;

use App\Consumers\Helpers\DBConnection;
use App\Manager\TokenManagerInterface;
use App\SmartContract\Model\ContractUpdateCallbackMessage;
use Doctrine\ORM\EntityManagerInterface;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;

class ContractUpdateConsumer implements ConsumerInterface
{
    /** @var LoggerInterface */
    private $logger;

    /** @var TokenManagerInterface */
    private $tokenManager;

    /** @var EntityManagerInterface */
    private $em;

    public function __construct(
        LoggerInterface $logger,
        TokenManagerInterface $tokenManager,
        EntityManagerInterface $em
    ) {
        $this->logger = $logger;
        $this->tokenManager = $tokenManager;
        $this->em = $em;
    }

    /** {@inheritdoc} */
    public function execute(AMQPMessage $msg)
    {
        DBConnection::reconnectIfDisconnected($this->em);

        $this->logger->info('[contract-update-consumer] Received new message: '.json_encode($msg->body));

        /** @var string|null $body */
        $body = $msg->body;

        try {
            $clbResult = ContractUpdateCallbackMessage::parse(
                json_decode((string)$body, true)
            );
        } catch (\Throwable $exception) {
            $this->logger->warning(
                '[contract-update-consumer] Failed to parse incoming message',
                [$msg->body]
            );

            return true;
        }

        try {
            $token = $this->tokenManager->findByAddress($clbResult->getTokenAddress());

            if (!$token) {
                $this->logger->info(
                    '[contract-update-consumer] Invalid token address "'.$clbResult->getTokenAddress().'" given'
                );

                return true;
            }

            $token->setMintDestination($clbResult->getMintDestination());

            if ($clbResult->getLock()) {
                $token->lockMintDestination();
            }

            $this->em->persist($token);
            $this->em->flush();
        } catch (\Throwable $exception) {
            $this->logger->error(
                '[contract-update-consumer] Failed to update token address. Retry operation. Reason:'
                .$exception->getMessage()
            );

            return false;
        }

        return true;
    }
}
