<?php declare(strict_types = 1);

namespace App\Command;

use App\Entity\Crypto;
use App\Entity\Profile;
use App\Entity\Token\Token;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Logger\UserActionLogger;
use App\Manager\CryptoManagerInterface;
use App\Manager\MarketStatusManagerInterface;
use App\Manager\ProfileManagerInterface;
use App\Manager\TokenManagerInterface;
use App\SmartContract\ContractHandlerInterface;
use App\Wallet\Money\MoneyWrapper;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use Money\Money;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CreateEthTokenCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'token:eth:create';

    private const INIT_BALANCE = '0';

    private EntityManagerInterface $em;
    private ProfileManagerInterface $profileManager;
    private CryptoManagerInterface $cryptoManager;
    private TokenManagerInterface $tokenManager;
    private BalanceHandlerInterface $balanceHandler;
    private MoneyWrapperInterface $moneyWrapper;
    private MarketFactoryInterface $marketManager;
    private MarketStatusManagerInterface $marketStatusManager;
    private ContractHandlerInterface $contractHandler;
    private UserActionLogger $logger;
    private ?Crypto $crypto;
    private ?Crypto $exchangeCrypto;

    public function __construct(
        EntityManagerInterface $em,
        ProfileManagerInterface $profileManager,
        CryptoManagerInterface $cryptoManager,
        TokenManagerInterface $tokenManager,
        BalanceHandlerInterface $balanceHandler,
        MoneyWrapperInterface $moneyWrapper,
        MarketFactoryInterface $marketManager,
        MarketStatusManagerInterface $marketStatusManager,
        ContractHandlerInterface $contractHandler,
        UserActionLogger $logger
    ) {
        $this->em = $em;
        $this->profileManager = $profileManager;
        $this->cryptoManager = $cryptoManager;
        $this->tokenManager = $tokenManager;
        $this->balanceHandler = $balanceHandler;
        $this->moneyWrapper = $moneyWrapper;
        $this->marketManager = $marketManager;
        $this->marketStatusManager = $marketStatusManager;
        $this->contractHandler = $contractHandler;
        $this->logger = $logger;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Add eth tokens to a user.')
            ->addArgument('tokenName', InputArgument::REQUIRED, 'The name of the token.')
            ->addArgument('email', InputArgument::REQUIRED, 'Email of the token owner.')
            ->addArgument('tokenAddress', InputArgument::REQUIRED, 'The address of the token')
            ->addOption(
                'minDeposit',
                'd',
                InputOption::VALUE_OPTIONAL,
                'Min deposit value (for ex.: 1.5, 2, 3.5 etc)'
            )
            ->addOption(
                'withdrawalFee',
                'f',
                InputOption::VALUE_OPTIONAL,
                'Withdrawal fee value (for ex.: 0.005, 7 etc.)'
            )
        ;
    }

    /** @inheritDoc */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $tokenName = $input->getArgument('tokenName');
        $email = $input->getArgument('email');
        $tokenAddress = $input->getArgument('tokenAddress');
        /** @var string|null $minDeposit */
        $minDeposit = $input->getOption('minDeposit');
        /** @var string|null $withdrawalFee */
        $withdrawalFee = $input->getOption('withdrawalFee');

        if ($minDeposit && !is_numeric($minDeposit) || $withdrawalFee && !is_numeric($withdrawalFee)) {
            $io->error('Wrong minimal deposit or withdrawal fee argument. Should be positive numeric (0.1, 2.5, 10)');
        }

        if (!is_string($tokenName) || !is_string($email) || !is_string($tokenAddress)) {
            $io->error('Wrong token/email/address name argument');

            return 1;
        }

        $profile = $this->profileManager->findByEmail($email);
        $this->crypto = $this->cryptoManager->findBySymbol(Token::ETH_SYMBOL);
        $this->exchangeCrypto = $this->cryptoManager->findBySymbol(Token::WEB_SYMBOL);
        $token = $this->tokenManager->findByName($tokenName)
            ?? $this->tokenManager->findByAddress($tokenAddress);
        $hasErrors = false;

        if (!$this->crypto || !$this->exchangeCrypto) {
            $hasErrors = true;
            $io->error('Cryptos don\'t exist');
        }

        if (!$profile) {
            $hasErrors = true;
            $io->error('email doesn\'t exist');
        }

        if ($token) {
            $hasErrors = true;
            $io->error('token name/address exists');
        }

        if ($hasErrors) {
            return 1;
        }

        $this->createEthToken($tokenName, $profile, $tokenAddress, $minDeposit, $withdrawalFee);
        $io->success("{$tokenName} added successfully");

        return 0;
    }

    private function createEthToken(
        string $name,
        Profile $profile,
        string $tokenAddress,
        ?string $minDeposit,
        ?string $withdrawalFee
    ): void {
        $this->em->beginTransaction();
        $token = $this->storeToken($name, $profile, $tokenAddress, $withdrawalFee);

        $this->balanceHandler->deposit(
            $profile->getUser(),
            $token,
            $this->moneyWrapper->parse(
                self::INIT_BALANCE,
                MoneyWrapper::TOK_SYMBOL
            )
        );

        $this->contractHandler->addToken($token, $minDeposit);

        $market = $this->marketManager->createUserRelated($profile->getUser());
        $this->marketStatusManager->createMarketStatus($market);
        $this->em->commit();
        $this->logger->info('Create eth token', ['name' => $token->getName(), 'id' => $token->getId()]);
    }

    private function storeToken(string $name, Profile $profile, string $tokenAddress, ?string $withdrawalFee): Token
    {
        $token = new Token();
        $token->setName($name)
            ->setAddress($tokenAddress)
            ->setExchangeCrypto($this->exchangeCrypto)
            ->setDeployed(new \DateTimeImmutable())
            ->setCrypto($this->crypto)
            ->setProfile($profile)
            ->setFee(
                $withdrawalFee ? $this->moneyWrapper->parse($withdrawalFee, MoneyWrapper::TOK_SYMBOL) : null
            );

        $this->em->persist($token);
        $this->em->flush();

        return $token;
    }
}
