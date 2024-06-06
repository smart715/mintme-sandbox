<?php declare(strict_types = 1);

namespace App\Consumers;

use App\Consumers\Helpers\DBConnection;
use App\Entity\Token\Token;
use App\Entity\Token\TokenDeploy;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenDeployManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Repository\TokenReleaseAddressHistoryRepository;
use App\SmartContract\Model\ChangeMintDestinationCallbackMessage;
use App\SmartContract\Model\ContractUpdateCallbackMessage;
use App\SmartContract\Model\UpdateMintedAmountCallbackMessage;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;

class ContractUpdateConsumer implements ConsumerInterface
{
    public const CHANGE_MINT_DESTINATION = 'changeMintDestination';
    public const UPDATE_MINTED_AMOUNT = 'updateMintedAmount';

    private LoggerInterface $logger;
    private EntityManagerInterface $em;
    private MoneyWrapperInterface $moneyWrapper;
    private TokenManagerInterface $tokenManager;
    private TokenDeployManagerInterface $tokenDeployManager;
    private CryptoManagerInterface $cryptoManager;
    private TokenReleaseAddressHistoryRepository $tokenReleaseAddressHistoryRepository;

    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $em,
        MoneyWrapperInterface $moneyWrapper,
        TokenManagerInterface $tokenManager,
        TokenDeployManagerInterface $tokenDeployManager,
        CryptoManagerInterface $cryptoManager,
        TokenReleaseAddressHistoryRepository $tokenReleaseAddressHistoryRepository
    ) {
        $this->logger = $logger;
        $this->em = $em;
        $this->moneyWrapper = $moneyWrapper;
        $this->tokenManager = $tokenManager;
        $this->tokenDeployManager = $tokenDeployManager;
        $this->cryptoManager = $cryptoManager;
        $this->tokenReleaseAddressHistoryRepository = $tokenReleaseAddressHistoryRepository;
    }

    /** {@inheritdoc} */
    public function execute(AMQPMessage $msg)
    {
        if (!DBConnection::initConsumerEm(
            'contract-update-consumer',
            $this->em,
            $this->logger
        )) {
            return false;
        }

        /** @var string $body */
        $body = $msg->body;

        $this->logger->info("[contract-update-consumer] Received new message: {$body}");

        try {
            $clbResult = ContractUpdateCallbackMessage::parse(json_decode($body, true));
        } catch (\Throwable $exception) {
            $this->logger->warning("[contract-update-consumer] Failed to parse incoming message", [$msg->body]);

            return true;
        }

        if (self::CHANGE_MINT_DESTINATION === $clbResult->getMethod()) {
            try {
                $clbMessage = ChangeMintDestinationCallbackMessage::parse($clbResult->getMessage());
            } catch (\Throwable $exception) {
                $this->logger->warning(
                    "[contract-update-consumer] (change mint destination) Failed to parse incoming message",
                    [$clbResult->getMessage()]
                );

                return true;
            }

            return $this->changeMintDestination($clbMessage);
        } elseif (self::UPDATE_MINTED_AMOUNT === $clbResult->getMethod()) {
            try {
                $clbMessage = UpdateMintedAmountCallbackMessage::parse($clbResult->getMessage());
            } catch (\Throwable $exception) {
                $this->logger->warning(
                    "[contract-update-consumer] (update mint amount) Failed to parse incoming message",
                    [$clbResult->getMessage()]
                );

                return true;
            }

            return $this->updateMintedAmount($clbMessage);
        } else {
            $this->logger->warning("[contract-update-consumer] Invalid method", [$clbResult->getMethod()]);

            return true;
        }
    }

    private function changeMintDestination(ChangeMintDestinationCallbackMessage $clbResult): bool
    {
        try {
            sleep(1);
            $this->em->clear();

            /** @var TokenDeploy|null $deploy */
            $deploy = $this->tokenDeployManager->findByAddress($clbResult->getTokenAddress());

            if (!$deploy) {
                $this->logger->warning(
                    "[contract-update-consumer] Invalid token address: {$clbResult->getTokenAddress()}"
                );

                return true;
            }

            $token = $deploy->getToken();

            if ($token->getMainDeploy()->getId() !== $deploy->getId()) {
                $this->logger->warning(
                    '[contract-update-consumer] token address is not from main deploy: ' .
                    $clbResult->getTokenAddress()
                );

                return true;
            }

            $newAddress = $clbResult->getMintDestination();
            $token->setMintDestination($newAddress);
            $history = $this->tokenReleaseAddressHistoryRepository->findLatestPending($token);

            if ($history) {
                !$newAddress || $newAddress === $history->getOldAddress()
                    ? $history->setErrorStatus()
                    : $history->setPaidStatus();

                $this->em->persist($history);
            } else {
                $this->logger->warning(
                    '[contract-update-consumer] Could not find any pending TokenReleaseAddressHistory to update'
                );
            }

            $this->em->persist($token);
            $this->em->flush();
        } catch (\Throwable $exception) {
            $this->logger->error(
                "[contract-update-consumer] Failed to update token address.
                 Retry operation. Reason: {$exception->getMessage()}"
            );

            return false;
        }

        return true;
    }

    private function updateMintedAmount(UpdateMintedAmountCallbackMessage $clbResult): bool
    {
        try {
            sleep(1);
            $this->em->clear();

            /** @var Token|null $token */
            $token = $this->tokenManager->findByName($clbResult->getTokenName());

            if (!$token) {
                $this->logger->warning(
                    "[contract-update-consumer] Invalid token name '{$clbResult->getTokenName()}' given"
                );

                return true;
            }

            $crypto = $this->cryptoManager->findBySymbol($clbResult->getCryptoSymbol());

            $deploy = $token->getDeployByCrypto($crypto);

            if (!$deploy || $deploy->getId() !== $token->getMainDeploy()->getId()) {
                $this->logger->warning(
                    "[contract-update-consumer] skipping minted amount update, " .
                    "{$token->getName()}/{$crypto->getSymbol()} is not main deploy"
                );

                return true;
            }

            $minted = $this->moneyWrapper->parse($clbResult->getValue(), Symbols::TOK);

            $token->setMintedAmount($token->getMintedAmount()->add($minted));

            $this->em->persist($token);
            $this->em->flush();
        } catch (\Throwable $exception) {
            $this->logger->error(
                "[contract-update-consumer] Failed to update token minted amount.
             Retry operation. Reason: {$exception->getMessage()}"
            );

            return false;
        }

        return true;
    }
}
