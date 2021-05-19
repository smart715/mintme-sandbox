<?php declare(strict_types = 1);

namespace App\Command;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Order;
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
    private MarketFactoryInterface $marketFactory;
    private CryptoManagerInterface $cryptoManager;
    private EntityRepository $userRepository;
    private EntityRepository $tokenRepository;
    private BlockTokenCommand $blockTokenCommand;

    public function __construct(
        MarketFactoryInterface $marketFactory,
        CryptoManagerInterface $cryptoManager,
        EntityManagerInterface $entityManager,
        BlockTokenCommand $blockTokenCommand
    ) {
        $this->cryptoManager = $cryptoManager;
        $this->marketFactory = $marketFactory;
        $this->userRepository = $entityManager->getRepository(User::class);
        $this->tokenRepository = $entityManager->getRepository(Token::class);
        $this->blockTokenCommand = $blockTokenCommand;
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

        foreach ($this->userRepository->findBy(['isBlocked' => true]) as $user) {
            $coinMarkets = $this->marketFactory->getCoinMarkets();
            $this->blockTokenCommand->cancelCoinOrders($user, $coinMarkets);
        }

        foreach ($this->tokenRepository->findBy(['isBlocked' => true]) as $token) {
            $tokenMarket = $this->marketFactory->create(
                $this->cryptoManager->findBySymbol((string)$token->getCryptoSymbol(), true),
                $token
            );
            $this->blockTokenCommand->cancelTokenOrders($tokenMarket, Order::BUY_SIDE);
            $this->blockTokenCommand->cancelTokenOrders($tokenMarket, Order::SELL_SIDE);
        }

        $io->success('orders for old user/token blocked has been cancelled');

        return 0;
    }
}
