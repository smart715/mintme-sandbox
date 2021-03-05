<?php declare(strict_types = 1);

namespace App\Command;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\ExchangerInterface;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market\MarketHandlerInterface;
use App\Manager\CryptoManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CancelOrdersForOldBlockedCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'app:cancel-orders';

    private MarketHandlerInterface $marketHandler;

    private MarketFactoryInterface $marketFactory;

    private CryptoManagerInterface $cryptoManager;

    private ExchangerInterface $exchanger;

    private EntityManagerInterface $em;

    private int $maxActiveOrders;

    public function __construct(
        MarketHandlerInterface $marketHandler,
        MarketFactoryInterface $marketFactory,
        CryptoManagerInterface $cryptoManager,
        ExchangerInterface $exchanger,
        int $maxActiveOrders
    ) {
        $this->marketHandler = $marketHandler;
        $this->cryptoManager = $cryptoManager;
        $this->marketFactory = $marketFactory;
        $this->exchanger = $exchanger;
        $this->maxActiveOrders = $maxActiveOrders;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('cancel orders for old already tokens and user blocked');
    }

    /** @inheritDoc */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        foreach ($this->getUserRepository()->findBy(['isBlocked' => true]) as $user) {
            $coinMarkets = $this->marketFactory->getCoinMarkets();
            $userPendingOrders = $this->marketHandler->getPendingOrdersByUser(
                $user,
                $coinMarkets,
                0,
                $this->maxActiveOrders
            );

            foreach ($userPendingOrders as $order) {
                $market = $order->getMarket();
                $this->exchanger->cancelOrder($market, $order);
            }
        }

        foreach ($this->getTokenRepository()->findBy(['isBlocked' => true]) as $token) {
            $tokenMarket = $this->marketFactory->create(
                $this->cryptoManager->findBySymbol($token->getCryptoSymbol()),
                $token
            );
            $tokenPendingOrders = array_merge(
                $this->marketHandler->getPendingSellOrders(
                    $tokenMarket,
                    0,
                    $this->maxActiveOrders
                ),
                $this->marketHandler->getPendingBuyOrders(
                    $tokenMarket,
                    0,
                    $this->maxActiveOrders
                )
            );

            foreach ($tokenPendingOrders as $order) {
                $market = $order->getMarket();
                $this->exchanger->cancelOrder($market, $order);
            }
        }

        $io->success('orders for old user/token blocked has been cancelled');

        return 0;
    }

    private function getUserRepository(): EntityRepository
    {
        return $this->em->getRepository(User::class);
    }

    private function getTokenRepository(): EntityRepository
    {
        return $this->em->getRepository(Token::class);
    }
}
