<?php declare(strict_types = 1);

namespace App\Command\Crypto;

use App\Communications\GeckoCoin\GeckoCoinCommunicatorInterface;
use App\Entity\Crypto;
use App\Manager\CryptoManagerInterface;
use App\Manager\WrappedCryptoTokenManagerInterface;
use App\SmartContract\ContractHandlerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currency;
use Money\Money;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;

class AddCryptoCommand extends Command
{
    private const NAME_OPT = 'name';
    private const SYMBOL_OPT = 'symbol';
    private const SUBUNIT_OPT = 'subunit';
    private const NATIVE_SUBUNIT_OPT = 'native-subunit';
    private const NATIVE_COIN_OPT = 'native-coin';
    private const SHOW_SUBUNIT_OPT = 'show-subunit';
    private const FEE_OPT = 'fee';
    private const NO_BASE_OPT = 'no-base';
    private const NO_QUOTE_OPT = 'no-quote';
    private const NO_ETHEREUM_OPT = 'no-ethereum';
    private const BLOCKCHAIN_CRYPTO_OPT = 'blockchain-crypto';
    private const ADDRESS_OPT = 'address';
    private const DEPOSITS_OPT = 'deposits';
    private const WITHDRAWALS_OPT = 'withdrawals';
    private const TRADES_OPT = 'trades';
    private const ALLOW_ALL_OPT = 'allow-all-services';


    /** Default image to use for new currency icon */
    private const DEFAULT_NEW_CURRENCY_IMG = 'default_new_currency.svg';
    /** Fallback when a parameter cant be handled */
    private const CHANGE_ME = 'CHANGE_ME';

    private CryptoManagerInterface $cryptoManager;
    private WrappedCryptoTokenManagerInterface $wrappedCryptoManager;
    private ContractHandlerInterface $contractHandler;
    private EntityManagerInterface $entityManager;
    private GeckoCoinCommunicatorInterface $geckoCoinCommunicator;
    private string $parametersPath;
    private string $assetsImgDir;
    private string $publicDir;

    /**
     * Used for rollback
     */
    private ?array $oldFullParameters;

    /**
     * All parameters that are arrays indexed by crypto like
     *
     *      deploy_fees:
     *          ETH: 0.01
     *          BNB: 0.01
     *
     *  If the key has any special syntax or a function handler to get its value,
     *  add it in  AddCryptoCommand::SPECIAL_PARAMS
     *
     *  name: name of the parameter
     *  default: default value to set
     *  token (optional): If it applies for crypto tokens
     */
    private const BY_CRYPTO_PARAMS = [
        ['name' => 'token_withdrawal_fees', 'default' => 0.01],
        ['name' => 'token_internal_withdrawal_fees', 'default' => null],
        ['name' => 'crypto_internal_withdrawal_fees', 'default' => null, 'token' => true],
        ['name' => 'explorer_urls', 'default' => 'CHANGE_ME'],
        ['name' => 'market_costs', 'default' => 100, 'token' => true],
        ['name' => 'deploy_referral_rewards', 'default' => 0.1],
        ['name' => 'disable_deposits', 'default' => false, 'token' => true],
        ['name' => 'disable_withdrawals', 'default' => true, 'token' => true],
        ['name' => 'disable_coin_trades', 'default' => true, 'token' => true],
        ['name' => 'deploy_costs', 'default' => 99],
        ['name' => 'deploy_fees', 'default' => 0.01],
        ['name' => 'connect_costs', 'default' => 99],
        ['name' => 'connect_fees', 'default' => 0.01],
        ['name' => 'blockchain_deploy_status', 'default' => true],
        ['name' => 'enabled_cryptos', 'default' => true, 'token' => true],
        ['name' => 'quick_trade_min_amounts', 'default' => 0.1, 'token' => true],
    ];

    /**
     * Special parameters that require any custom handling
     *
     *  name: name of the parameter
     *  handler: class method to call
     *  token (optional): If it applies for crypto tokens
     */
    private const SPECIAL_PARAMS = [
        ['name' => 'coinbase_cryptos', 'handler' => 'handleCoinbaseCryptos', 'token' => true],
    ];

    public function __construct(
        CryptoManagerInterface $cryptoManager,
        WrappedCryptoTokenManagerInterface $wrappedCryptoManager,
        ContractHandlerInterface $contractHandler,
        EntityManagerInterface $entityManager,
        GeckoCoinCommunicatorInterface $geckoCoinCommunicator,
        string $parametersPath,
        string $assetsImgDir,
        string $publicDir
    ) {
        parent::__construct();

        $this->cryptoManager = $cryptoManager;
        $this->wrappedCryptoManager = $wrappedCryptoManager;
        $this->contractHandler = $contractHandler;
        $this->entityManager = $entityManager;
        $this->geckoCoinCommunicator = $geckoCoinCommunicator;
        $this->parametersPath = $parametersPath;
        $this->assetsImgDir = $assetsImgDir;
        $this->publicDir = $publicDir;
    }

    protected function configure(): void
    {
        $this
            ->setName('app:add-crypto')
            ->setDescription('Add a short description for your command')
            ->addOption(self::NAME_OPT, null, InputOption::VALUE_REQUIRED, 'Name e.g Binance coin')
            ->addOption(self::SYMBOL_OPT, null, InputOption::VALUE_REQUIRED, 'Blockchain Symbol e.g BNB')
            ->addOption(self::SUBUNIT_OPT, null, InputOption::VALUE_REQUIRED, 'Subunit in for our backend, use 18 by default')
            ->addOption(self::NATIVE_SUBUNIT_OPT, null, InputOption::VALUE_REQUIRED, 'Subunit in blockchain, real value')
            ->addOption(self::NATIVE_COIN_OPT, null, InputOption::VALUE_REQUIRED, 'Native coin in blockchain, can leave empty if same as symbol')
            ->addOption(self::SHOW_SUBUNIT_OPT, null, InputOption::VALUE_REQUIRED, 'Subunit to show in frontend e.g 8')
            ->addOption(self::FEE_OPT, null, InputOption::VALUE_REQUIRED, 'Fee for withdrawals e.g 0.003')
            ->addOption(
                self::BLOCKCHAIN_CRYPTO_OPT,
                null,
                InputOption::VALUE_OPTIONAL,
                '*ONLY IF IT IS A TOKEN (like USDC, USDT)*. Main crypto of its blockchain, e.g BNB',
                null
            )
            ->addOption(
                self::ADDRESS_OPT,
                null,
                InputOption::VALUE_OPTIONAL,
                '*ONLY IF IT IS A TOKEN (like USDC, USDT)*. Token address, e.g 0x55d39...',
                null
            )
            ->addOption(self::NO_BASE_OPT, null, InputOption::VALUE_NONE, 'If it CAN NOT be used as base for markets')
            ->addOption(self::NO_QUOTE_OPT, null, InputOption::VALUE_NONE, 'If it CAN NOT be used as quote for markets')
            ->addOption(self::NO_ETHEREUM_OPT, null, InputOption::VALUE_NONE, 'If its blockchain is NOT based on Ethereum')
            ->addOption(self::DEPOSITS_OPT, null, InputOption::VALUE_REQUIRED, 'Enable deposits ("yes" is default, "no" to disable)')
            ->addOption(self::WITHDRAWALS_OPT, null, InputOption::VALUE_REQUIRED, 'Enable withdrawals ("no" is default, "yes" to enable)')
            ->addOption(self::TRADES_OPT, null, InputOption::VALUE_REQUIRED, 'Enable trades ("no" is default, "yes" to disable)')
            ->addOption(self::ALLOW_ALL_OPT, null, InputOption::VALUE_NONE, 'Allow deposits, withdrawals and trades')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->entityManager->beginTransaction();

        try {
            $this->assertValidOptions($input, $output);

            $name = ucwords($input->getOption(self::NAME_OPT));
            $symbol = strtoupper($input->getOption(self::SYMBOL_OPT));
            $subunit = (int)$input->getOption(self::SUBUNIT_OPT);
            $nativeSubunit = (int)$input->getOption(self::NATIVE_SUBUNIT_OPT);
            $nativeCoin = (string)$input->getOption(self::NATIVE_COIN_OPT);
            $showSubunit = (int)$input->getOption(self::SHOW_SUBUNIT_OPT);
            $fee = bcmul($input->getOption(self::FEE_OPT), bcpow('10', (string)$subunit));
            /** @var bool $tradable */
            $tradable = !$input->getOption(self::NO_BASE_OPT);
            /** @var bool $exchangeble */
            $exchangeble = !$input->getOption(self::NO_QUOTE_OPT);
            /** @var bool $isEthereum */
            $isEthereum = !$input->getOption(self::NO_ETHEREUM_OPT);
            /** @var string|null $blockchainCryptoSymbol */
            $blockchainCryptoSymbol = $input->getOption(self::BLOCKCHAIN_CRYPTO_OPT);
            /** @var string|null $address */
            $address = $input->getOption(self::ADDRESS_OPT);

            $blockchainCrypto = $blockchainCryptoSymbol
                ? $this->getCrypto($blockchainCryptoSymbol)
                : null;

            $isToken = $blockchainCrypto && $address;
            $nativeCrypto = $nativeCoin
                ? $this->getCrypto($nativeCoin)
                : null;

            $crypto = $this->addCrypto(
                $io,
                $name,
                $symbol,
                $subunit,
                $nativeSubunit,
                $showSubunit,
                $tradable,
                $exchangeble,
                $isToken,
                $fee,
                $nativeCrypto
            );

            if ($isToken && $isEthereum) {
                $this->addTokenToGateway($io, $crypto, $blockchainCrypto, $address, $fee);
            } elseif (!$isToken && $crypto->isNative()) {
                $io->warning("Crypto '$symbol' is a native crypto. Gateway MUST be configured to handle it");
            } elseif ($nativeCrypto && !$crypto->isNative() && !$isToken) {
                $io->warning("'$symbol' is a Blockchain. Gateway MUST be configured to handle it");
                $this->addWrappedNativeCrypto($io, $crypto, $nativeCrypto, $fee);
            } else {
                $io->warning("Crypto '$symbol' is marked as non Ethereum compatible. Gateway MUST get code changes to handle it");
            }

            $overridedParams = $this->makeOverridedParams([
                self::DEPOSITS_OPT => $input->getOption(self::DEPOSITS_OPT),
                self::WITHDRAWALS_OPT => $input->getOption(self::WITHDRAWALS_OPT),
                self::TRADES_OPT => $input->getOption(self::TRADES_OPT),
                self::ALLOW_ALL_OPT => $input->getOption(self::ALLOW_ALL_OPT),
            ], $io);

            $this->addCryptoParameters($io, $symbol, $isToken, $overridedParams);
            $this->addCryptoAssets($io, $symbol);

            $this->entityManager->commit();

            $io->success('Crypto successfully added! Now follow the next instructions');
            $this->logManualInstructions($io, $symbol, $subunit, $showSubunit, $isToken);

            return 0;
        } catch (\Throwable $e) {
            $this->entityManager->rollback();
            $this->rollBackParameters();

            $io->warning('CATCHING ERROR! Database and parameter changes were rolled back');
            $io->error($e->getMessage());

            return 1;
        }
    }

    private function makeOverridedParams(array $userOptions, SymfonyStyle $io): array
    {
        $overridedParams = [];
        $allowAll = $userOptions[self::ALLOW_ALL_OPT];

        if ($allowAll) {
            $overridedParams["disable_deposits"] = false;
            $overridedParams["disable_withdrawals"] = false;
            $overridedParams["disable_coin_trades"] = false;

            return $overridedParams;
        }

        if ($userOptions[self::DEPOSITS_OPT]) {
            try {
                $value = $this->convertYesNoToBoolean($userOptions[self::DEPOSITS_OPT]);
                $overridedParams["disable_deposits"] = !$value;
            } catch (\Throwable $e) {
                $io->warning("Deposits option got wrong value. Default one is used");
            }
        }

        if ($userOptions[self::WITHDRAWALS_OPT]) {
            try {
                $value = $this->convertYesNoToBoolean($userOptions[self::WITHDRAWALS_OPT]);
                $overridedParams["disable_withdrawals"] = !$value;
            } catch (\Throwable $e) {
                $io->warning("Withdrawals option got wrong value. Default one is used");
            }
        }

        if ($userOptions[self::TRADES_OPT]) {
            try {
                $value = $this->convertYesNoToBoolean($userOptions[self::TRADES_OPT]);
                $overridedParams["disable_coin_trades"] = !$value;
            } catch (\Throwable $e) {
                $io->warning("Trades option got wrong value. Default one is used");
            }
        }

        return $overridedParams;
    }

    private function addCrypto(
        SymfonyStyle $io,
        string $name,
        string $symbol,
        int $subunit,
        int $nativeSubunit,
        int $showSubunit,
        bool $tradable,
        bool $exchangeble,
        bool $isToken,
        string $fee,
        ?Crypto $nativeCrypto
    ): Crypto {
        if ($crypto = $this->cryptoManager->findBySymbol($symbol, true)) {
            $io->warning("Crypto '$symbol' is already added. Skipping");

            return $crypto;
        }

        $crypto = $this->cryptoManager->create(
            $name,
            $symbol,
            $subunit,
            $nativeSubunit,
            $showSubunit,
            $tradable,
            $exchangeble,
            $isToken,
            $isToken ? null : $fee,
            $nativeCrypto
        );

        $io->success('Crypto successfully added to Panel Database!');

        return $crypto;
    }

    private function addTokenToGateway(
        SymfonyStyle $io,
        Crypto $crypto,
        Crypto $blockchainCrypto,
        string $address,
        string $fee
    ): void {
        $io->writeln('Since it is a crypto token, adding it to the Gateway automatically...');

        $addTokenResult = $this->contractHandler->addToken($crypto, $blockchainCrypto, $address, null, true);

        if ($addTokenResult->alreadyExisted()) {
            $io->warning('Gateway already had ' . $crypto->getSymbol().'/'.$blockchainCrypto->getSymbol() .' added.');
        }

        if ($addTokenResult->getDecimals() !== $crypto->getSubunit()) {
            $realDecimals = $addTokenResult->getDecimals();
            $subunit = $crypto->getSubunit();

            $io->warning("Crypto decimals is set to $subunit, but the token in blockchain has $realDecimals decimals, please review it");
        }

        $io->writeln("Adding the '". $blockchainCrypto->getSymbol() . "' blockchain relation...");

        if ($this->wrappedCryptoManager->findByCryptoAndDeploy($crypto, $blockchainCrypto)) {
            $io->warning('Relation already exists. Skipping...');

            return;
        }

        /*
         * We can't use MoneyWrapper since this currency is still not fully enabled, it won't accept this currency.
         * Fee was previously parsed
         */
        $feeMoney = new Money($fee, new Currency($crypto->getMoneySymbol()));
        $this->wrappedCryptoManager->create($crypto, $blockchainCrypto, $address, $feeMoney);
    }

    public function addWrappedNativeCrypto(SymfonyStyle $io, Crypto $blockchain, Crypto $nativeCoin, string $fee): void
    {
        $feeMoney = new Money($fee, new Currency($nativeCoin->getMoneySymbol()));

        $this->wrappedCryptoManager->create($nativeCoin, $blockchain, null, $feeMoney);
    }

    private function addCryptoParameters(SymfonyStyle $io, string $symbol, bool $isToken, array $overridedParams): void
    {
        /** @var array */
        $fullParametersFile = $this->oldFullParameters = Yaml::parseFile($this->parametersPath);
        $parameters = $fullParametersFile['parameters'];

        $io->writeln("Setting default parameters for $symbol:");

        $hasWritten = false;

        foreach (self::BY_CRYPTO_PARAMS as $paramInfo) {
            if (array_key_exists($paramInfo['name'], $overridedParams)) {
                $paramInfo['default'] = $overridedParams[$paramInfo['name']];
            }

            $processed = $this->processParams($parameters, $paramInfo, $io, $symbol, $isToken);

            if ($processed) {
                $hasWritten = true;
            }
        }

        foreach (self::SPECIAL_PARAMS as $paramInfo) {
            $processed = $this->processParams($parameters, $paramInfo, $io, $symbol, $isToken);

            if ($processed) {
                $hasWritten = true;
            }
        }

        if ($hasWritten) {
            $fullParametersFile['parameters'] = $parameters;

            \file_put_contents($this->parametersPath, Yaml::dump($fullParametersFile, 99));

            $io->success("Default parameters for '$symbol' has been set!");

            return;
        }

        $this->oldFullParameters = null;
        $io->warning("No default parameters has been written for '$symbol'");
    }

    private function processParams(
        array &$parameters,
        array $paramInfo,
        SymfonyStyle $io,
        string $symbol,
        bool $isToken
    ): bool {
        /** @var string */
        $paramName = $paramInfo['name'];
        /** @var bool */
        $paramToken = $paramInfo['token'] ?? false;
        /** @var callable|null */
        $handlerName = $paramInfo['handler'] ?? null;
        /** @var mixed|null */
        $default = $paramInfo['default'] ?? null;

        if (!$paramToken && $isToken) {
            return false;
        }

        $paramArray = $parameters[$paramName] ?? null;

        if (!$paramArray || !is_array($paramArray)) {
            $io->writeln("The parameter '$paramName' does not exist or it is not an array. Skipping...");

            return false;
        }

        if (array_key_exists($symbol, $paramArray)) {
            $paramValue = $paramArray[$symbol];

            $io->writeln("The parameter '$paramName' already has '$symbol' set (".var_export($paramValue, true)."). Skipping...");

            return false;
        }

        if ($handlerName) {
            /** @phpstan-ignore-next-line */
            $paramHandlerResponse = call_user_func_array([$this, $handlerName], [$io, $symbol]);

            if (!$paramHandlerResponse) {
                return false;
            }

            $default = $paramHandlerResponse;
        }

        $parameters[$paramName][$symbol] = $default;

        $io->writeln("- $paramName.$symbol: ".var_export($default, true));

        return true;
    }

    private function rollBackParameters(): void
    {
        if (isset($this->oldFullParameters)) {
            \file_put_contents($this->parametersPath, Yaml::dump($this->oldFullParameters, 99));
        }
    }

    private function addCryptoAssets(SymfonyStyle $io, string $symbol): void
    {
        $crypto = $this->getCrypto($symbol);
        $defaultImgUrl = $this->assetsImgDir . self::DEFAULT_NEW_CURRENCY_IMG;

        $io->writeln("Setting default icons for $symbol. Manually change them later");

        $iconPaths = [
            $this->publicDir . $crypto->getImage()->getUrl(),
            $this->assetsImgDir . $symbol . '.svg',
            $this->assetsImgDir . $symbol . '_avatar.svg',
        ];

        foreach ($iconPaths as $iconPath) {
            if (file_exists($iconPath)) {
                $io->warning("Icon $iconPath already exists. Skipping...");

                continue;
            }

            copy($defaultImgUrl, $iconPath);
            $io->writeln("Icon $iconPath set!");
        }
    }

    private function getCrypto(string $symbol): Crypto
    {
        $crypto = $this->cryptoManager->findBySymbol($symbol, true);

        if (!$crypto) {
            throw new \Exception("Crypto $symbol was not found");
        }

        return $crypto;
    }

    private function assertValidOptions(InputInterface $input, OutputInterface $output): void
    {
        $io = new SymfonyStyle($input, $output);
        $opts = $input->getOptions();
        $errors = [];

        // Required check
        $requiredOpts = [
            self::NAME_OPT,
            self::SYMBOL_OPT,
            self::SUBUNIT_OPT,
            self::SHOW_SUBUNIT_OPT,
            self::FEE_OPT,
        ];

        foreach ($requiredOpts as $requiredOpt) {
            if (!$opts[$requiredOpt]) {
                $errors[] = "'$requiredOpt' is required!";
            }
        }

        // Number like check
        $numericOpts = [self::SUBUNIT_OPT, self::SHOW_SUBUNIT_OPT, self::FEE_OPT];

        foreach ($numericOpts as $numericOpt) {
            $opt = $opts[$numericOpt] ?? null;

            if ($opt && !is_numeric($opt)) {
                $errors[] = "'$numericOpt' must be numeric!";
            }
        }

        // Token
        if ((bool)$opts[self::BLOCKCHAIN_CRYPTO_OPT] !== (bool)$opts[self::ADDRESS_OPT]) {
            $errors[] = "Both options must be present or none: '" . join(
                "', '",
                [self::BLOCKCHAIN_CRYPTO_OPT, self::ADDRESS_OPT]
            ) . "'";
        }

        if (count($errors) > 0) {
            array_walk($errors, fn($error) => $io->error($error));

            exit(1);
        }
    }

    private function logManualInstructions(
        SymfonyStyle $io,
        string $symbol,
        int $subunit,
        int $showSubunit,
        bool $isToken
    ): void {
        $io->writeln("
            1. If you are adding a coin, Add in Viabtc matchengine config.json the new crypto and then restart it: 

            \"assets\": [
                \\\\ ...
                {
                  \"name\": \"$symbol\",
                  \"prec_save\": $subunit,
                  \"prec_show\": $showSubunit
                }
            ],
            \"token_markets\": [
                \\\\ ...
                {
                  \"name\": \"$symbol\",
                  \"stock_prec\": $showSubunit,
                  \"money_prec\": $showSubunit,
                  \"min_amount\": \"0.000001\" \\\\ This one has to be consulted
                }
            ],
        ");

        $io->writeln("
            2. Update translations in <comment>/translations/crypto/messages.{lang}.yml</comment> files. Add following translation keys if needed:
                ".(!$isToken ? "<error>!!!required!!!</error> <comment>\"dynamic.blockchain_{$symbol}_name\"</comment> - name of blockchain" : "")."
                <comment>\"dynamic.deposit_modal.accept_warning_$symbol\"</comment> - warning message that shows in deposit modal (not required, \"deposit_modal.accept_warning\" message will be shown instead)
                <comment>\"dynamic.withdraw_modal_message_$symbol\"</comment> - warning message that shows in withdrawal modal (not required, if not added then this message will not be shown)
                <comment>\"dynamic.withdraw_modal_address_label_$symbol\"</comment> - custom withdraw modal address label (not required)
                ".(!$isToken ? "<comment>\"dynamic.trading.deployed.label_$symbol\"</comment> - label for 'deployed on' filter on trading page (not required)" : "")."
        ");

        $io->writeln("
            3. For withdraw API if the network of $symbol is different than it's currency or if you want to allow another network symbol, add it under conversion_map parameter in parameters.yaml file. using this syntax as example:
                conversion_map:
                    POLYGON: MATIC
                    THE_NEW_SYMBOL: $symbol
        ");

        $io->writeln("4. Clear cache (php bin/console cache:clear)");
        $io->writeln("5. Load frontend translations (php bin/console app:load-translations-ui)");
        $io->writeln("6. Rebuild frot end (npm run prod)");
        $io->writeln("7. Restart consumers");
    }

    /**
     * @return mixed
     */
    private function handleCoinbaseCryptos(SymfonyStyle $io, string $symbol) // phpcs:ignore
    {
        $coinList = $this->geckoCoinCommunicator->getCoinList();

        foreach ($coinList as $coin) {
            if ($coin['symbol'] === strtolower($symbol)) {
                $id = $coin['id'];

                $io->warning("A coingecko id was found for $symbol: '$id'");

                return $id;
            }
        }

        $io->warning("A coingecko id for $symbol could not be found, please set it manually");

        return self::CHANGE_ME;
    }

    private function convertYesNoToBoolean(string $value): bool
    {
        if (null == $value || !in_array($value, ["yes", "no"])) {
            throw new \InvalidArgumentException('Wrong input');
        }

        return "yes" === $value;
    }
}
