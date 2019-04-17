<?php declare(strict_types = 1);

namespace App\Exchange\Trade;

use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
use App\Entity\User;
use App\Exchange\Market;
use App\Exchange\Order;
use App\Exchange\Trade\Config\LimitOrderConfig;
use App\Exchange\Trade\Config\OrderFilterConfig;
use App\Exchange\Trade\Config\PrelaunchConfig;
use App\Repository\UserRepository;
use App\Utils\Converter\MarketNameConverterInterface;
use App\Utils\DateTimeInterface;
use App\Wallet\Money\MoneyWrapper;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currency;
use Money\Money;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class Trader implements TraderInterface
{
    /** @var TraderFetcherInterface */
    private $fetcher;

    /** @var LimitOrderConfig */
    private $config;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var MoneyWrapperInterface */
    private $moneyWrapper;

    /** @var PrelaunchConfig */
    private $prelaunchConfig;

    /** @var DateTimeInterface */
    private $time;

    /** @var MarketNameConverterInterface */
    private $marketNameConverter;

    /** @var LoggerInterface */
    private $logger;

    /** @var NormalizerInterface */
    private $normalizer;

    public function __construct(
        TraderFetcherInterface $fetcher,
        LimitOrderConfig $config,
        EntityManagerInterface $entityManager,
        MoneyWrapperInterface $moneyWrapper,
        PrelaunchConfig $prelaunchConfig,
        DateTimeInterface $time,
        MarketNameConverterInterface $marketNameConverter,
        NormalizerInterface $normalizer,
        LoggerInterface $logger
    ) {
        $this->fetcher = $fetcher;
        $this->config = $config;
        $this->entityManager = $entityManager;
        $this->moneyWrapper = $moneyWrapper;
        $this->prelaunchConfig = $prelaunchConfig;
        $this->time = $time;
        $this->marketNameConverter = $marketNameConverter;
        $this->normalizer = $normalizer;
        $this->logger = $logger;
    }

    public function placeOrder(Order $order): TradeResult
    {
        $result = $this->fetcher->placeOrder(
            $order->getMaker()->getId(),
            $this->marketNameConverter->convert($order->getMarket()),
            $order->getSide(),
            $this->moneyWrapper->format($order->getAmount()),
            $this->moneyWrapper->format($order->getPrice()),
            (string)$this->config->getTakerFeeRate(),
            (string)$this->config->getMakerFeeRate(),
            $this->isReferralFeeEnabled() ? $order->getReferralId() : 0,
            $this->isReferralFeeEnabled() ? (string)$this->prelaunchConfig->getReferralFee() : '0'
        );

        $quote = $order->getMarket()->getQuote();

        if (TradeResult::SUCCESS === $result->getResult() && $quote instanceof Token) {
            $this->updateUserReferrencer($order->getMaker(), $quote);
        }

        if (TradeResult::FAILED === $result->getResult()) {
            $this->logger->error(
                "Failed to place new order for user {$order->getMaker()->getEmail()}. 
                Reason: {$result->getMessage()}",
                (array)$this->normalizer->normalize($result, null, [
                    'groups' => ['Default'],
                ])
            );
        }

        return $result;
    }

    public function cancelOrder(Order $order): TradeResult
    {
        $result = $this->fetcher->cancelOrder(
            $order->getMaker()->getId(),
            $this->marketNameConverter->convert($order->getMarket()),
            $order->getId() ?? 0
        );

        if (TradeResult::FAILED === $result->getResult()) {
            $this->logger->error(
                "Failed to cancel order '{$order->getId()}' for user {$order->getMaker()->getEmail()}. 
                Reason: {$result->getMessage()}"
            );
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getFinishedOrders(User $user, Market $market, array $filterOptions = []): array
    {
        $options = new OrderFilterConfig();
        $options->merge($filterOptions);

        $records = $this->fetcher->getFinishedOrders(
            $user->getId(),
            $this->marketNameConverter->convert($market),
            $options['start_time'],
            $options['end_time'],
            $options['offset'],
            $options['limit'],
            Order::SIDE_MAP[$options['side']]
        );

        return array_map(function (array $rawOrder) use ($user, $market) {
            return $this->createOrder($rawOrder, $user, $market, Order::FINISHED_STATUS);
        }, $records);
    }

    /**
     * @inheritdoc
     */
    public function getPendingOrders(User $user, Market $market, array $filterOptions = []): array
    {
        $options = new OrderFilterConfig();
        $options->merge($filterOptions);

        $records = $this->fetcher->getPendingOrders(
            $user->getId(),
            $this->marketNameConverter->convert($market),
            $options['offset'],
            $options['limit'],
            Order::SIDE_MAP[$options['side']]
        );

        return array_map(function (array $rawOrder) use ($user, $market) {
            return $this->createOrder($rawOrder, $user, $market, Order::PENDING_STATUS);
        }, $records);
    }

    private function isReferralFeeEnabled(): bool
    {
        return !$this->prelaunchConfig->isEnabled() &&
            $this->prelaunchConfig->getTradeFinishDate()->getTimestamp() < $this->time->now()->getTimestamp();
    }

    private function updateUserReferrencer(User $user, Token $token): void
    {
        $referrencer = $user->getReferrencer();

        if ($referrencer && !in_array($referrencer, $token->getRelatedUsers(), true)) {
            $referrencer->addRelatedToken($token);
            $this->entityManager->persist($referrencer);
            $this->entityManager->flush();
        }
    }

    private function createOrder(array $orderData, User $user, Market $market, string $status): Order
    {
        return new Order(
            $orderData['id'],
            $user,
            null,
            $market,
            new Money(
                $orderData['amount'],
                new Currency($this->getSymbol($market->getQuote()))
            ),
            $orderData['side'],
            new Money(
                $orderData['price'],
                new Currency($this->getSymbol($market->getQuote()))
            ),
            $status,
            $orderData['mtime']
        );
    }

    private function getSymbol(TradebleInterface $tradeble): string
    {
        return $tradeble instanceof Token
            ? MoneyWrapper::TOK_SYMBOL
            : $tradeble->getSymbol();
    }
}
