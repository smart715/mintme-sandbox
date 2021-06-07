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
use App\Manager\ScheduledNotificationManagerInterface;
use App\Manager\TokenManagerInterface;
use App\SmartContract\ContractHandlerInterface;
use App\Utils\NotificationTypes;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CreateBlockchainTokenCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'token:create';

    private const INIT_BALANCE = '0';

    private const ETH_BLOCKCHAIN = 'ETH';
    private const BNB_BLOCKCHAIN = 'BNB';
    private const ALLOWED_BLOCKCHAINS = [
        self::ETH_BLOCKCHAIN,
        self::BNB_BLOCKCHAIN,
    ];

    private EntityManagerInterface $em;
    private ProfileManagerInterface $profileManager;
    private CryptoManagerInterface $cryptoManager;
    private TokenManagerInterface $tokenManager;
    private ScheduledNotificationManagerInterface $scheduledNotificationManager;
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
        ScheduledNotificationManagerInterface $scheduledNotificationManager,
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
        $this->scheduledNotificationManager = $scheduledNotificationManager;
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
            ->setDescription('Add ethereum or binance tokens to a user.')
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
            ->addOption(
                'blockchain',
                'b',
                InputOption::VALUE_REQUIRED,
                'Specify token\'s blockchain, ETH or BNB (Ethereum or Binance)'
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
        /** @var string $blockchain */
        $blockchain = $input->getOption('blockchain');

        if (!$blockchain || !is_string($blockchain) ||  !in_array($blockchain, self::ALLOWED_BLOCKCHAINS)) {
            $io->error('Wrong blockchain parameter. Allowed: '.implode(', ', self::ALLOWED_BLOCKCHAINS));

            return 1;
        }

        if ($minDeposit && !is_numeric($minDeposit) || $withdrawalFee && !is_numeric($withdrawalFee)) {
            $io->error('Wrong minimal deposit or withdrawal fee argument. Should be positive numeric (0.1, 2.5, 10)');

            return 1;
        }

        if (!is_string($tokenName) || !is_string($email) || !is_string($tokenAddress)) {
            $io->error('Wrong token/email/address name argument');

            return 1;
        }

        $profile = $this->profileManager->findByEmail($email);
        $this->crypto = $this->cryptoManager->findBySymbol($blockchain);
        $this->exchangeCrypto = $this->cryptoManager->findBySymbol(Symbols::WEB);
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

        if ($profile->hasTokens()) {
            $hasErrors = true;
            $io->error('User with provided email has already created a token');
        }

        $contractDecimals = (int)$this->contractHandler->getDecimalsContract($tokenAddress, $blockchain);

        if ($minDeposit && !$this->checkDecimals($minDeposit, $contractDecimals)) {
            $hasErrors = true;
            $io->error('Min deposit with more decimals than allowed for eth token. Allowed: '.$contractDecimals);
        }

        if ($withdrawalFee && !$this->checkDecimals($withdrawalFee, $contractDecimals)) {
            $hasErrors = true;
            $io->error('Withdraw fee with more decimals than allowed for eth token. Allowed: '.$contractDecimals);
        }

        if ($hasErrors) {
            return 1;
        }

        if (!$this->createBlockchainToken($tokenName, $profile, $tokenAddress, $minDeposit, $withdrawalFee)) {
            $io->error('Please make sure that the internal services are running then try again!');

            return 1;
        }

        $io->success("{$tokenName} added successfully");

        return 0;
    }

    private function createBlockchainToken(
        string $name,
        Profile $profile,
        string $tokenAddress,
        ?string $minDeposit,
        ?string $withdrawalFee
    ): bool {
        $this->em->beginTransaction();
        $token = $this->storeToken($name, $profile, $tokenAddress, $withdrawalFee);
        $symbol = $this->crypto->getSymbol();

        try {
            $this->balanceHandler->deposit(
                $profile->getUser(),
                $token,
                $this->moneyWrapper->parse(
                    self::INIT_BALANCE,
                    Symbols::TOK
                )
            );
            $this->contractHandler->addToken($token, $minDeposit);
        } catch (\Throwable $exception) {
            $this->logger->error('error while adding '.$symbol.'token: ' . json_encode($exception));

            return false;
        }

        $market = $this->marketManager->createUserRelated($profile->getUser());
        $this->marketStatusManager->createMarketStatus($market);

        $this->scheduledNotificationManager->createScheduledNotification(
            NotificationTypes::MARKETING_AIRDROP_FEATURE,
            $token->getOwner()
        );

        $this->em->commit();
        $this->logger->info('Create '.$symbol.' token', ['name' => $token->getName(), 'id' => $token->getId()]);

        return true;
    }

    private function storeToken(string $name, Profile $profile, string $tokenAddress, ?string $withdrawalFee): Token
    {
        $token = new Token();
        $token->setName($name)
            ->setAddress($tokenAddress)
            ->setExchangeCrypto($this->exchangeCrypto)
            ->setDeployedDate(new \DateTimeImmutable())
            ->setDeployed(true)
            ->setCrypto($this->crypto)
            ->setProfile($profile)
            ->setFee(
                $withdrawalFee ? $this->moneyWrapper->parse($withdrawalFee, Symbols::TOK) : null
            );

        $this->em->persist($token);
        $this->em->flush();

        return $token;
    }

    private function checkDecimals(string $numericString, int $contractDecimals): bool
    {
        $explodeDigits = explode('.', $numericString);

        return !(isset($explodeDigits[1]) && $contractDecimals < strlen(rtrim($explodeDigits[1], '0')));
    }
}
