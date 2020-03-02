<?php declare(strict_types = 1);

namespace App\Consumers;

use App\Consumers\Helpers\DBConnection;
use App\Entity\Token\Token;
use App\SmartContract\Model\ChangeMintDestinationCallbackMessage;
use App\SmartContract\Model\ContractUpdateCallbackMessage;
use App\SmartContract\Model\UpdateMintedAmountCallbackMessage;
use App\Wallet\Money\MoneyWrapperInterface;
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

    /** @var MoneyWrapperInterface */
    private $moneyWrapper;

    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $em,
        MoneyWrapperInterface $moneyWrapper
    ) {
        $this->logger = $logger;
        $this->em = $em;
        $this->moneyWrapper = $moneyWrapper;
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

        if ('updateMintDestination' === $clbResult->getMethod()) {
            try {
                $clbMessage = ChangeMintDestinationCallbackMessage::parse(json_decode($clbResult->getMessage(), true));
            } catch (\Throwable $exception) {
                $this->logger->warning("[contract-update-consumer] Failed to parse incoming message", [$clbResult->getMessage()]);

                return true;
            }

            return $this->updateMintDestination($clbMessage);
        } elseif ('updateMintedAmount' === $clbResult->getMethod()) {
            try {
                $clbMessage = UpdateMintedAmountCallbackMessage::parse(json_decode($clbResult->getMessage(), true));
            } catch (\Throwable $exception) {
                $this->logger->warning("[contract-update-consumer] Failed to parse incoming message", [$clbResult->getMessage()]);

                return true;
            }

            return $this->updateMintedAmount($clbMessage);
        } else {
            $this->logger->warning("[contract-update-consumer] Invalid method", [$clbResult->getMethod()]);

            return true;
        }
    }

    private function updateMintDestination(ChangeMintDestinationCallbackMessage $clbResult): bool
    {
        try {
            $repo = $this->em->getRepository(Token::class);
            $token = $repo->findOneBy(['address' => $clbResult->getTokenAddress()]);

            if (!$token) {
                $this->logger->info("[contract-update-consumer] Invalid token address '{$clbResult->getTokenAddress()}' given");

                return true;
            }

            $token->setMintDestination($clbResult->getMintDestination());

            $this->em->persist($token);
            $this->em->flush();
        } catch (\Throwable $exception) {
            $this->logger->error("[contract-update-consumer] Failed to update token address. Retry operation. Reason: {$exception->getMessage()}");

            return false;
        }

        return true;
    }

    private function updateMintedAmount(UpdateMintedAmountCallbackMessage $clbResult): bool
    {
        try {
            $repo = $this->em->getRepository(Token::class);
            $token = $repo->findOneBy(['name' => $clbResult->getTokenName()]);

            if (!$token) {
                $this->logger->info("[contract-update-consumer] Invalid token name '{$clbResult->getTokenName()}' given");

                return true;
            }

            $minted = $this->moneyWrapper->parse($clbResult->getValue(), Token::TOK_SYMBOL);

            $token->setMintedAmount($token->getMintedAmount()->add($minted));

            $this->em->persist($token);
            $this->em->flush();
        } catch (\Throwable $exception) {
            $this->logger->error("[contract-update-consumer] Failed to update token minted amount. Retry operation. Reason: {$exception->getMessage()}");

            return false;
        }

        return true;
    }
}
