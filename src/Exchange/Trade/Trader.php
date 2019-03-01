<?php

namespace App\Exchange\Trade;

use App\Communications\Exception\FetchException;
use App\Communications\JsonRpcInterface;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Market;
use App\Exchange\Order;
use App\Exchange\Trade\Config\LimitOrderConfig;
use App\Exchange\Trade\Config\OrderFilterConfig;
use App\Exchange\Trade\Config\PrelaunchConfig;
use App\Repository\UserRepository;
use App\Utils\Converter\MarketNameConverterInterface;
use App\Utils\DateTimeInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currency;
use Money\Money;

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

    public function __construct(
        TraderFetcherInterface $fetcher,
        LimitOrderConfig $config,
        EntityManagerInterface $entityManager,
        MoneyWrapperInterface $moneyWrapper,
        PrelaunchConfig $prelaunchConfig,
        DateTimeInterface $time,
        MarketNameConverterInterface $marketNameConverter
    ) {
        $this->fetcher = $fetcher;
        $this->config = $config;
        $this->entityManager = $entityManager;
        $this->moneyWrapper = $moneyWrapper;
        $this->prelaunchConfig = $prelaunchConfig;
        $this->time = $time;
        $this->marketNameConverter = $marketNameConverter;
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

        if (TradeResult::SUCCESS === $result->getResult()) {
            $maker = $this->getUserRepository()->find($order->getMaker()->getId());
            $taker = $this->getUserRepository()->find($order->getTaker() ? $order->getTaker()->getId() : 0);

            $token = $order->getMarket()->getToken();

            if (null !== $token) {
                $this->updateUsers([$maker, $taker], $token);
            }
        }

        return $result;
    }

    public function cancelOrder(Order $order): TradeResult
    {
        return $this->fetcher->cancelOrder(
            $order->getMaker()->getId(),
            $this->marketNameConverter->convert($order->getMarket()),
            $order->getId() ?? 0
        );
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

    /**
     * @param User[] $users
     */
    private function updateUsers(array $users, Token $token): void
    {
        foreach ($users as $user) {
            if (null !== $user && !in_array($token, $user->getRelatedTokens())) {
                $user->addRelatedToken($token);
                $this->entityManager->persist($user);
                $this->entityManager->flush();
            }
        }
    }

    private function getUserRepository(): UserRepository
    {
        return $this->entityManager->getRepository(User::class);
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
                new Currency($market->getCurrencySymbol())
            ),
            $orderData['side'],
            new Money(
                $orderData['price'],
                new Currency($market->getCurrencySymbol())
            ),
            $status,
            $orderData['mtime']
        );
    }
}
