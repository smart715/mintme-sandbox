<?php declare(strict_types = 1);

namespace App\Exchange\Trade;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
use App\Entity\User;
use App\Entity\UserCrypto;
use App\Entity\UserToken;
use App\Events\OrderEvent;
use App\Exchange\Market;
use App\Exchange\Order;
use App\Exchange\Trade\Config\LimitOrderConfig;
use App\Exchange\Trade\Config\OrderFilterConfig;
use App\Utils\Converter\MarketNameConverterInterface;
use App\Wallet\Money\MoneyWrapper;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currency;
use Money\Money;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
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

    /** @var MarketNameConverterInterface */
    private $marketNameConverter;

    /** @var LoggerInterface */
    private $logger;

    /** @var NormalizerInterface */
    private $normalizer;

    /** @var float */
    private $referralFee;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    public function __construct(
        TraderFetcherInterface $fetcher,
        LimitOrderConfig $config,
        EntityManagerInterface $entityManager,
        MoneyWrapperInterface $moneyWrapper,
        MarketNameConverterInterface $marketNameConverter,
        NormalizerInterface $normalizer,
        LoggerInterface $logger,
        float $referralFee,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->fetcher = $fetcher;
        $this->config = $config;
        $this->entityManager = $entityManager;
        $this->moneyWrapper = $moneyWrapper;
        $this->marketNameConverter = $marketNameConverter;
        $this->normalizer = $normalizer;
        $this->logger = $logger;
        $this->referralFee = $referralFee;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function placeOrder(Order $order, bool $updateTokenOrCrypto = true): TradeResult
    {
        $result = $this->fetcher->placeOrder(
            $order->getMaker()->getId(),
            $this->marketNameConverter->convert($order->getMarket()),
            $order->getSide(),
            $this->moneyWrapper->format($order->getAmount()),
            $this->moneyWrapper->format($order->getPrice()),
            (string)$this->config->getTakerFeeRate(),
            (string)$this->config->getMakerFeeRate(),
            $order->getReferralId() ?: 0,
            $this->referralFee ? (string)$this->referralFee : '0'
        );

        $quote = $order->getMarket()->getQuote();

        if (TradeResult::SUCCESS === $result->getResult()) {
            /** @psalm-suppress TooManyArguments */
            $this->eventDispatcher->dispatch(new OrderEvent($order), OrderEvent::CREATED);

            if ($updateTokenOrCrypto) {
                if ($quote instanceof Token) {
                    $this->updateUserTokenReferencer($order->getMaker(), $quote);
                } elseif ($quote instanceof Crypto) {
                    $this->updateUserCrypto($order->getMaker(), $quote);
                }
            }
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

    private function updateUserTokenReferencer(User $user, Token $token): void
    {
        $referencer = $user->getReferencer();

        if (!in_array($user, $token->getUsers(), true)) {
            $userToken = (new UserToken())->setToken($token)->setUser($user);
            $this->entityManager->persist($userToken);
            $user->addToken($userToken);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }

        if ($referencer && !in_array($referencer, $token->getUsers(), true)) {
            $userToken = (new UserToken())->setToken($token)->setUser($referencer);
            $this->entityManager->persist($userToken);
            $referencer->addToken($userToken);
            $this->entityManager->persist($referencer);
            $this->entityManager->flush();
        }
    }

    private function updateUserCrypto(User $user, Crypto $crypto): void
    {
        if (!in_array($user, $crypto->getUsers(), true)) {
            $userCrypto = new UserCrypto($user, $crypto);
            $user->addCrypto($userCrypto);
            $this->entityManager->persist($user);
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
            null,
            $orderData['mtime'] ?? null,
        );
    }

    private function getSymbol(TradebleInterface $tradeble): string
    {
        return $tradeble instanceof Token
            ? MoneyWrapper::TOK_SYMBOL
            : $tradeble->getSymbol();
    }
}
