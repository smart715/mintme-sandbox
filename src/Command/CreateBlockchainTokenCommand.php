<?php declare(strict_types = 1);

namespace App\Command;

use App\Activity\ActivityTypes;
use App\Entity\Crypto;
use App\Entity\Profile;
use App\Entity\Token\Token;
use App\Entity\Token\TokenDeploy;
use App\Entity\TokenCrypto;
use App\Events\Activity\TokenEventActivity;
use App\Events\Activity\TokenImportedEvent;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Logger\UserActionLogger;
use App\Mailer\MailerInterface;
use App\Manager\BlacklistManagerInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\MarketStatusManagerInterface;
use App\Manager\ProfileManagerInterface;
use App\Manager\ScheduledNotificationManagerInterface;
use App\Manager\TokenDeployManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Notifications\Strategy\NotificationContext;
use App\Notifications\Strategy\TokenPromotionNotificationStrategy;
use App\SmartContract\ContractHandlerInterface;
use App\Utils\Converter\RebrandingConverterInterface;
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
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CreateBlockchainTokenCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'token:create';

    private const DEPOSITS_OPT = 'deposits';
    private const WITHDRAWALS_OPT = 'withdrawals';
    private const TRADES_OPT = 'trades';
    private const ALLOW_ALL_OPT = 'allow-all-services';

    private const INIT_BALANCE = '0';

    private int $tokenCreateLimit;

    private EntityManagerInterface $em;
    private ProfileManagerInterface $profileManager;
    private CryptoManagerInterface $cryptoManager;
    private TokenManagerInterface $tokenManager;
    private TokenDeployManagerInterface $tokenDeployManager;
    private ScheduledNotificationManagerInterface $scheduledNotificationManager;
    private BalanceHandlerInterface $balanceHandler;
    private MoneyWrapperInterface $moneyWrapper;
    private MarketFactoryInterface $marketManager;
    private MarketStatusManagerInterface $marketStatusManager;
    private ContractHandlerInterface $contractHandler;
    private UserActionLogger $logger;
    private ?Crypto $crypto;
    private ?Crypto $exchangeCrypto;
    private RebrandingConverterInterface $rebrandingConverter;
    private BlacklistManagerInterface $blacklistManager;
    private MailerInterface $mailer;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        EntityManagerInterface $em,
        ProfileManagerInterface $profileManager,
        CryptoManagerInterface $cryptoManager,
        TokenManagerInterface $tokenManager,
        TokenDeployManagerInterface $tokenDeployManager,
        ScheduledNotificationManagerInterface $scheduledNotificationManager,
        BalanceHandlerInterface $balanceHandler,
        MoneyWrapperInterface $moneyWrapper,
        MarketFactoryInterface $marketManager,
        MarketStatusManagerInterface $marketStatusManager,
        ContractHandlerInterface $contractHandler,
        UserActionLogger $logger,
        RebrandingConverterInterface $rebrandingConverter,
        BlacklistManagerInterface $blacklistManager,
        MailerInterface $mailer,
        EventDispatcherInterface $eventDispatcher,
        int $tokenCreateLimit
    ) {
        $this->em = $em;
        $this->profileManager = $profileManager;
        $this->cryptoManager = $cryptoManager;
        $this->tokenManager = $tokenManager;
        $this->tokenDeployManager = $tokenDeployManager;
        $this->scheduledNotificationManager = $scheduledNotificationManager;
        $this->balanceHandler = $balanceHandler;
        $this->moneyWrapper = $moneyWrapper;
        $this->marketManager = $marketManager;
        $this->marketStatusManager = $marketStatusManager;
        $this->contractHandler = $contractHandler;
        $this->logger = $logger;
        $this->rebrandingConverter = $rebrandingConverter;
        $this->blacklistManager = $blacklistManager;
        $this->tokenCreateLimit = $tokenCreateLimit;
        $this->mailer = $mailer;
        $this->eventDispatcher = $eventDispatcher;
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
                'Specify token\'s blockchain, ETH, BNB, or CRO (Ethereum, Binance, Crypto.com)'
            )
            ->addOption(
                'hasTax',
                't',
                InputOption::VALUE_NONE,
                'flag to indicate if token has tax, default is false',
            )
            ->addOption(
                'priceDecimals',
                'pd',
                InputOption::VALUE_OPTIONAL,
                'Specify subunits for token price for all markets. For ex.: 12 => 0.000000000001'
            )
            ->addOption(self::DEPOSITS_OPT, null, InputOption::VALUE_REQUIRED, 'Enable deposits ("yes" is default, "no" to disable)')
            ->addOption(self::WITHDRAWALS_OPT, null, InputOption::VALUE_REQUIRED, 'Enable withdrawals ("no" is default, "yes" to enable)')
            ->addOption(self::TRADES_OPT, null, InputOption::VALUE_REQUIRED, 'Enable trades ("no" is default, "yes" to disable)')
            ->addOption(self::ALLOW_ALL_OPT, null, InputOption::VALUE_NONE, 'Allow deposits, withdrawals and trades')
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
        /** @var bool $hasTax */
        $hasTax = $input->getOption('hasTax');
        $allowedBlockchains = $this->getAllowedBlockchains();
        /** @var string|int|null $priceDecimals */
        $priceDecimals = $input->getOption('priceDecimals');

        if (!$blockchain || !is_string($blockchain) || !in_array($blockchain, $allowedBlockchains)) {
            $io->error('Wrong blockchain parameter. Allowed: ' . implode(', ', $allowedBlockchains));

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

        if ($this->blacklistManager->isBlackListedToken($tokenName)) {
            $io->error('Forbidden token name');

            return 1;
        }

        if (!is_null($priceDecimals) && 0 === (int)$priceDecimals || 18 < (int)$priceDecimals) {
            $io->error('Price decimals should be between 1 and 18');

            return 1;
        }

        $blockchain = $this->rebrandingConverter->reverseConvert($blockchain);
        $profile = $this->profileManager->findByEmail($email);
        $this->crypto = $this->cryptoManager->findBySymbol($blockchain);
        $this->exchangeCrypto = $this->cryptoManager->findBySymbol(Symbols::WEB);

        $token = $this->tokenManager->findByName($tokenName);

        $deploy = $this->tokenDeployManager->findByAddressAndCrypto($tokenAddress, $this->crypto);

        $hasErrors = false;

        if (!$this->crypto || !$this->exchangeCrypto) {
            $hasErrors = true;
            $io->error('Cryptos don\'t exist');
        }

        if (!$profile) {
            $hasErrors = true;
            $io->error('email doesn\'t exist');
        }

        if ($token || $deploy) {
            $hasErrors = true;
            $io->error('token name/address exists');
        }

        if ($profile->getTokensCount() >= (int)$this->tokenCreateLimit) {
            $hasErrors = true;
            $io->error('User has reached the maximum amount of tokens');
        }

        $contractDecimals = (int)$this->contractHandler->getDecimalsContract($tokenAddress, $blockchain);

        if ($minDeposit && !$this->checkDecimals($minDeposit, $contractDecimals)) {
            $hasErrors = true;
            $io->error(
                'Min deposit with more decimals than allowed for '
                .$this->crypto->getSymbol()
                .' token. Allowed: '.$contractDecimals
            );
        }

        if ($withdrawalFee && !$this->checkDecimals($withdrawalFee, $contractDecimals)) {
            $hasErrors = true;
            $io->error(
                'Withdraw fee with more decimals than allowed for '
                .$this->crypto->getSymbol()
                .' token. Allowed: '.$contractDecimals
            );
        }

        if ($hasErrors) {
            return 1;
        }

        $tokenServicesSettings = $this->makeTokenServicesSettings([
            self::DEPOSITS_OPT => $input->getOption(self::DEPOSITS_OPT),
            self::WITHDRAWALS_OPT => $input->getOption(self::WITHDRAWALS_OPT),
            self::TRADES_OPT => $input->getOption(self::TRADES_OPT),
            self::ALLOW_ALL_OPT => $input->getOption(self::ALLOW_ALL_OPT),
        ], $io);

        if (!$this->createBlockchainToken(
            $tokenName,
            $profile,
            $tokenAddress,
            $minDeposit,
            $withdrawalFee,
            $hasTax,
            $tokenServicesSettings,
            $priceDecimals ? (int)$priceDecimals : null
        )) {
            $io->error('Please make sure that the internal services are running then try again!');

            return 1;
        }

        $io->success("{$tokenName} added successfully");

        return 0;
    }

    private function makeTokenServicesSettings(array $userOptions, SymfonyStyle $io): array
    {
        $tokenSettings = [];
        $allowAll = $userOptions[self::ALLOW_ALL_OPT];

        if ($allowAll) {
            $tokenSettings[self::DEPOSITS_OPT] = false;
            $tokenSettings[self::WITHDRAWALS_OPT] = false;
            $tokenSettings[self::TRADES_OPT] = false;

            return $tokenSettings;
        }

        if ($userOptions[self::DEPOSITS_OPT]) {
            try {
                $value = $this->convertYesNoToBoolean($userOptions[self::DEPOSITS_OPT]);
                $tokenSettings[self::DEPOSITS_OPT] = !$value;
            } catch (\Throwable $e) {
                $io->warning("Deposits option got wrong value. Default one is used");
            }
        }

        if ($userOptions[self::WITHDRAWALS_OPT]) {
            try {
                $value = $this->convertYesNoToBoolean($userOptions[self::WITHDRAWALS_OPT]);
                $tokenSettings[self::WITHDRAWALS_OPT] = !$value;
            } catch (\Throwable $e) {
                $io->warning("Withdrawals option got wrong value. Default one is used");
            }
        }

        if ($userOptions[self::TRADES_OPT]) {
            try {
                $value = $this->convertYesNoToBoolean($userOptions[self::TRADES_OPT]);
                $tokenSettings[self::TRADES_OPT] = !$value;
            } catch (\Throwable $e) {
                $io->warning("Trades option got wrong value. Default one is used");
            }
        }

        return $tokenSettings;
    }

    private function createBlockchainToken(
        string $name,
        Profile $profile,
        string $tokenAddress,
        ?string $minDeposit,
        ?string $withdrawalFee,
        bool $hasTax,
        array $tokenServicesSettings,
        ?int $priceDecimals
    ): bool {
        $this->em->beginTransaction();
        $token = $this->storeToken(
            $name,
            $profile,
            $tokenAddress,
            $withdrawalFee,
            $hasTax,
            $tokenServicesSettings,
            $priceDecimals
        );
        $symbol = $this->crypto->getSymbol();

        try {
            $this->balanceHandler->beginTransaction();

            $initBalance = $this->moneyWrapper->parse(
                self::INIT_BALANCE,
                Symbols::TOK
            );

            $this->balanceHandler->deposit(
                $profile->getUser(),
                $token,
                $initBalance
            );

            $result = $this->contractHandler->addToken(
                $token,
                $token->getCrypto(),
                $token->getMainDeploy()->getAddress(),
                $minDeposit
            );

            $token->setDecimals($result->getDecimals());
            $token->setIsPausable($result->isPausable());
        } catch (\Throwable $exception) {
            $this->balanceHandler->rollback();
            $this->logger->error('error while adding '.$symbol.'token: ' . json_encode($exception));

            return false;
        }

        $market = $this->marketManager->createUserRelated($profile->getUser());
        $this->marketStatusManager->createMarketStatus($market);

        $notificationContext = new NotificationContext(new TokenPromotionNotificationStrategy(
            $this->mailer,
            $token,
        ));
        $notificationContext->sendNotification($profile->getUser());

        $this->scheduledNotificationManager->createScheduledNotification(
            NotificationTypes::MARKETING_AIRDROP_FEATURE,
            $token->getOwner(),
            false,
        );

        $this->scheduledNotificationManager->createScheduledTokenNotification(
            NotificationTypes::TOKEN_PROMOTION,
            $token,
        );

        $this->eventDispatcher->dispatch(
            new TokenImportedEvent($token, $token->getMainDeploy()),
            TokenImportedEvent::NAME
        );

        $this->em->flush();
        $this->em->commit();
        $this->logger->info('Create '.$symbol.' token', ['name' => $token->getName(), 'id' => $token->getId()]);

        return true;
    }

    private function storeToken(
        string $name,
        Profile $profile,
        string $tokenAddress,
        ?string $withdrawalFee,
        bool $hasTax,
        array $tokenServicesSettings,
        ?int $priceDecimals
    ): Token {
        $token = new Token();
        $tokenCrypto = new TokenCrypto();
        $tokenCrypto
            ->setToken($token)
            ->setCrypto($this->exchangeCrypto);

        $deploy = (new TokenDeploy())
            ->setToken($token)
            ->setCrypto($this->crypto)
            ->setAddress($tokenAddress)
            ->setDeployDate(new \DateTimeImmutable());

        $token->setName($name)
            ->setDeployed(true)
            ->addDeploy($deploy)
            ->setProfile($profile)
            ->setFee(
                $withdrawalFee ? $this->moneyWrapper->parse($withdrawalFee, Symbols::TOK) : null
            )
            ->addExchangeCrypto($tokenCrypto)
            ->setCreatedOnMintmeSite(false)
            ->setHasTax($hasTax)
            ->setDepositsDisabled($tokenServicesSettings[self::DEPOSITS_OPT] ?? false)
            ->setWithdrawalsDisabled($tokenServicesSettings[self::WITHDRAWALS_OPT] ?? true)
            ->setTradesDisabled($tokenServicesSettings[self::TRADES_OPT] ?? true)
            ->setPriceDecimals($priceDecimals);

        if (Symbols::WEB === $token->getCryptoSymbol()) {
            $token->setIsHidden(true);
        }

        $this->em->persist($deploy);
        $this->em->persist($token);
        $this->em->flush();

        return $token;
    }

    private function checkDecimals(string $numericString, int $contractDecimals): bool
    {
        $explodeDigits = explode('.', $numericString);

        return !(isset($explodeDigits[1]) && $contractDecimals < strlen(rtrim($explodeDigits[1], '0')));
    }

    private function convertYesNoToBoolean(string $value): bool
    {
        if (null == $value || !in_array($value, ["yes", "no"])) {
            throw new \InvalidArgumentException('Wrong input');
        }

        return "yes" === $value;
    }

    private function getAllowedBlockchains(): array
    {
        return array_map(static fn ($blockchain) => $blockchain->getSymbol(), $this->cryptoManager->findAll());
    }
}
