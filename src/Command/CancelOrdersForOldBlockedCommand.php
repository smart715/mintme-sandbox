<?php declare(strict_types = 1);

namespace App\Command;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\ExchangerInterface;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market\MarketHandlerInterface;
use App\Logger\UserActionLogger;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Manager\UserManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CancelOrdersForOldBlockedCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'app:cancelOrders';

    /** @var TokenManagerInterface */
    private $tokenManager;

    /** @var UserManagerInterface */
    private $userManager;

    /** @var EntityManagerInterface */
    private $em;

    /** @var UserActionLogger */
    private $logger;

    /** @var MarketHandlerInterface */
    private $marketHandler;

    /** @var MarketFactoryInterface */
    private $marketFactory;

    /** @var CryptoManagerInterface */
    private $cryptoManager;

    /** @var ExchangerInterface */
    private $exchanger;

    private int $maxActiveOrders;

    public function __construct(
        TokenManagerInterface $tokenManager,
        UserManagerInterface $userManager,
        EntityManagerInterface $em,
        UserActionLogger $logger,
        MarketHandlerInterface $marketHandler,
        MarketFactoryInterface $marketFactory,
        CryptoManagerInterface $cryptoManager,
        ExchangerInterface $exchanger,
        int $maxActiveOrders
    ) {
        $this->tokenManager = $tokenManager;
        $this->userManager = $userManager;
        $this->em = $em;
        $this->logger = $logger;
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

        foreach ($this->userManager->getBlockedUsers() as $user) {

            // todo get all user blocked
        }

        foreach ($this->tokenManager->getBlockedTokens()) {
            // todo get all token blocked

        }







        $io->success('user/token orders has been cancel');

        return 0;
    }
}
