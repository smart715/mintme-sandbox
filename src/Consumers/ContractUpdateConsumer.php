<?php declare(strict_types = 1);

namespace App\Consumers;

use App\Consumers\Helpers\DBConnection;
use App\Entity\Token\Token;
use App\SmartContract\Model\ContractUpdateCallbackMessage;
use Doctrine\ORM\EntityManagerInterface;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;

class ContractUpdateConsumer implements ConsumerInterface
{
    /** @var LoggerInterface */
    private $logger;

    /** @var EntityManagerInterface */
    private $em;

    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $em
    ) {
        $this->logger = $logger;
        $this->em = $em;
    }

    /** {@inheritdoc} */
    public function execute(AMQPMessage $msg)
    {
        DBConnection::reconnectIfDisconnected($this->em);

        /** @var string $body */
        $body = $msg->body ?? '';

        $this->logger->info("[contract-update-consumer] Received new message: {$body}");

        try {
            $clbResult = ContractUpdateCallbackMessage::parse(json_decode($body, true));
        } catch (\Throwable $exception) {
            $this->logger->warning("[contract-update-consumer] Failed to parse incoming message", [$msg->body]);

            return true;
        }

        try {
            $repo = $this->em->getRepository(Token::class);
            $token = $repo->findOneBy(['address' => $clbResult->getTokenAddress()]);

            if (!$token) {
                $this->logger->info("[contract-update-consumer] Invalid token address '{$clbResult->getTokenAddress()}' given");

                return true;
            }

            $token->setMintDestination($clbResult->getMintDestination());

            if ($clbResult->getLock()) {
                $token->lockMintDestination();
            }

            $this->em->persist($token);
            $this->em->flush();
        } catch (\Throwable $exception) {
            $this->logger->error("[contract-update-consumer] Failed to update token address. Retry operation. Reason: {$exception->getMessage()}");

            return false;
        }

        return true;
    }
}
