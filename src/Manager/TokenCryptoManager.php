<?php declare(strict_types = 1);

namespace App\Manager;

use App\Activity\ActivityTypes;
use App\Communications\MarketCostFetcherInterface;
use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TokenCrypto;
use App\Events\MarketEvent;
use App\Events\TokenEvents;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Balance\Exception\BalanceException;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Mailer\MailerInterface;
use App\Notifications\Strategy\MarketCreatedNotificationStrategy;
use App\Notifications\Strategy\NotificationContext;
use App\Repository\TokenCryptoRepository;
use App\Utils\NotificationTypes;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class TokenCryptoManager implements TokenCryptoManagerInterface
{
    public const OPEN_MARKET_ID = 'open_market';

    private EntityManagerInterface $entityManager;
    private BalanceHandlerInterface $balanceHandler;
    private MarketCostFetcherInterface $marketCostFetcher;
    private MarketStatusManagerInterface $marketStatusManager;
    private MarketFactoryInterface $marketFactory;
    private TokenCryptoRepository $repository;
    private EventDispatcherInterface $eventDispatcher;
    private UserNotificationManagerInterface $userNotificationManager;
    private MailerInterface $mailer;
    private UserTokenFollowManagerInterface $userTokenFollowManager;

    public function __construct(
        EntityManagerInterface $entityManager,
        BalanceHandlerInterface $balanceHandler,
        MarketCostFetcherInterface $marketCostFetcher,
        MarketStatusManagerInterface $marketStatusManager,
        MarketFactoryInterface $marketFactory,
        TokenCryptoRepository $repository,
        EventDispatcherInterface $eventDispatcher,
        UserNotificationManagerInterface $userNotificationManager,
        MailerInterface $mailer,
        UserTokenFollowManagerInterface $userTokenFollowManager
    ) {
        $this->entityManager = $entityManager;
        $this->balanceHandler = $balanceHandler;
        $this->marketCostFetcher = $marketCostFetcher;
        $this->marketStatusManager = $marketStatusManager;
        $this->marketFactory = $marketFactory;
        $this->repository = $repository;
        $this->eventDispatcher = $eventDispatcher;
        $this->userNotificationManager = $userNotificationManager;
        $this->mailer = $mailer;
        $this->userTokenFollowManager = $userTokenFollowManager;
    }

    public function createTokenCrypto(Crypto $payCrypto, Crypto $marketCrypto, Token $token): void
    {
        $user = $token->getOwner();
        $balance = $this->balanceHandler->balance($user, $payCrypto)->getAvailable();
        $marketCost = $this->marketCostFetcher->getCost($marketCrypto->getSymbol())[$payCrypto->getSymbol()];

        if ($balance->lessThan($marketCost)) {
            throw new BalanceException('Not enough balance to open market');
        }

        try {
            $this->balanceHandler->beginTransaction();
            $this->balanceHandler->update(
                $user,
                $payCrypto,
                $marketCost->negative(),
                self::OPEN_MARKET_ID
            );
        } catch (\Throwable $e) {
            $this->balanceHandler->rollback();

            throw $e;
        }

        $userCrypto = new TokenCrypto();
        $userCrypto
            ->setCrypto($marketCrypto)
            ->setToken($token)
            ->setCost($marketCost)
            ->setCryptoCost($payCrypto);

        $this->marketStatusManager->createMarketStatus([$this->marketFactory->create($marketCrypto, $token)]);

        $this->entityManager->persist($userCrypto);
        $this->entityManager->flush();

        /** @psalm-suppress TooManyArguments */
        $this->eventDispatcher->dispatch(
            new MarketEvent($userCrypto, ActivityTypes::MARKET_CREATED),
            TokenEvents::MARKET_CREATED
        );

        $strategy = new MarketCreatedNotificationStrategy(
            $userCrypto,
            NotificationTypes::MARKET_CREATED,
            $this->userNotificationManager,
            $this->mailer
        );
        $notificationContext = new NotificationContext($strategy);
        $followers = $this->userTokenFollowManager->getFollowers($token);

        foreach ($followers as $follower) {
            $notificationContext->sendNotification($follower);
        }
    }

    public function getByCryptoAndToken(Crypto $crypto, Token $token): ?TokenCrypto
    {
        return $this->repository->findOneBy(['crypto' => $crypto, 'token' => $token]);
    }

    public function getTotalCostPerCrypto(DateTimeImmutable $startDate, DateTimeImmutable $endDate): array
    {
        return $this->repository->getTotalCostPerCrypto($startDate, $endDate);
    }
}
