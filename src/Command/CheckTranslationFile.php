<?php declare(strict_types = 1);

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;
use Translation\Bundle\Catalogue\CatalogueFetcher;
use Translation\Bundle\Model\Configuration;
use Translation\Bundle\Service\ConfigurationManager;

class CheckTranslationFile extends Command
{
    private ConfigurationManager $configurationManager;
    private CatalogueFetcher $catalogueFetcher;
    private string $defaultLocale;
    private Filesystem $filesystem;

    public function __construct(
        string $defaultLocale,
        ConfigurationManager $configurationManager,
        CatalogueFetcher $catalogueFetcher,
        Filesystem $filesystem
    ) {
        $this->defaultLocale = $defaultLocale;

        parent::__construct();

        $this->configurationManager = $configurationManager;
        $this->catalogueFetcher = $catalogueFetcher;
        $this->filesystem = $filesystem;
    }

    protected function configure(): void
    {
        $this
            ->setName('translation:check')
            ->setDescription('Check that all translations keys across the tranlations files.')
            ->addOption('reference', 'r', InputOption::VALUE_REQUIRED, 'the locale used as reference', $this->defaultLocale)
            ->addArgument('configuration', InputArgument::OPTIONAL, 'The configuration to use', 'default')
            ->addArgument('locales', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'the locales to compare', ['*']);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $config = $this->getConfig($input);
        $locales = $this->getLocales($config, $input);
        $reference = $this->getReference($config, $input);
        $result = $this->getCompareResult($config, $reference, $locales);

        $this->printResult($input, $output, $result);

        return 0;
    }

    /**
     * Returns the list of locales to use.
     *
     * @param InputInterface $input the imput interface
     * @return Configuration The config params
     * @throws InvalidArgumentException When argument given is not a string
     */
    protected function getConfig(InputInterface $input): Configuration
    {
        $configName = $input->getArgument('configuration');

        if ('string' !== gettype($configName)) {
            throw new InvalidArgumentException('The configuration name argument must be a string');
        }

        return $this->configurationManager->getConfiguration($configName);
    }

    /**
     * Returns the list of locales to use.
     *
     * @param Configuration $config The translation config php representation
     * @param InputInterface $input the wanted locales list
     * @return string[] The locales
     * @throws InvalidArgumentException When argument given is not a string
     */
    protected function getLocales(Configuration $config, InputInterface $input): array
    {
        $configLocales = $config->getLocales();
        /** @var array */
        $locales = $input->getArgument('locales');

        if ('array' !== gettype($locales)) {
            throw new InvalidArgumentException('The locales argument must be a array');
        }

        if (in_array('*', $locales)) {
            return $configLocales;
        }

        return array_filter($locales, function (string $locale) use ($configLocales) {
            return in_array($locale, $configLocales);
        });
    }

    /**
     * Returns the locale used as reference.
     *
     * @param Configuration $config The translation config php representation
     * @param InputInterface $input the wanted locale used as reference
     * @return string Reference locale
     * @throws InvalidArgumentException When argument given doesn't exist or is not a string
     */
    protected function getReference(Configuration $config, InputInterface $input): string
    {
        $configLocales = $config->getLocales();
        /** @var string */
        $reference = $input->getOption('reference');

        if ('string' !== gettype($reference)) {
            throw new InvalidArgumentException('The locale reference argument must be a string');
        }

        if (!in_array($reference, $configLocales)) {
            throw new InvalidArgumentException("locale $reference dont exist in translation locales");
        }
        
        return $reference;
    }

    protected function getDomains(Configuration $config, array $locales): array
    {
        $catalogues = $this->catalogueFetcher->getCatalogues($config, $locales);

        return array_reduce($catalogues, function ($domains, $catalogue) {
            return array_replace($domains, $catalogue->getDomains());
        }, []);
    }

    protected function getLocaleFiles(
        Configuration $config,
        string $locale,
        array $domains,
        bool $keys = false
    ): array {
        return array_reduce($domains, function ($files, $domain) use ($config, $locale, $keys) {
            $file = Yaml::parseFile($config->getOutputDir() . "/$domain.$locale.yml");

            $files[$domain] = $keys
                ? array_keys($file)
                : $file;

            return $files;
        }, []);
    }
    protected function getCompareResult(Configuration $config, string $reference, array $locales): array
    {
        $domains = $this->getDomains($config, $locales);
        $referenceFile = $this->getLocaleFiles($config, $reference, $domains, true);
        $resultLog = [
            "reference" => $reference,
            "locales" => implode(" | ", $locales),
            "compared" => 0,
            "missing" => 0,
        ];

        return array_reduce($locales, function ($resultLog, $locale) use ($config, $domains, $referenceFile) {
            $resultLog = $this->compareFiles($config, $referenceFile, $locale, $domains, $resultLog);

            return $resultLog;
        }, $resultLog);
    }

    protected function compareFiles(
        Configuration $config,
        array $referenceFile,
        string $locale,
        array $domains,
        array $resultLog
    ): array {
        $files = $this->getLocaleFiles($config, $locale, $domains);

        array_walk($domains, function ($domain) use (
            &$resultLog,
            $files,
            $locale,
            $config,
            $referenceFile
        ): void {
            $resultLog['compared'] += 1;
            [$comparedFile, $resultLog] = $this->compareKeys(
                $referenceFile[$domain],
                $files[$domain]
                    ?? [],
                $resultLog
            );
    
            $this->filesystem->dumpFile(
                $config->getOutputDir() . "/$domain.$locale.yml",
                Yaml::dump($comparedFile, 2)
            );
        });

        return $resultLog;
    }

    protected function comparekeys(array $referenceFile, array $file, array $resultLog): array
    {
        array_walk($referenceFile, function ($key) use (&$file, &$resultLog): void {
            if (!key_exists($key, $file)) {
                $file[$key] = '';
                $resultLog['missing'] += 1;
            }
        });

        return [$file, $resultLog];
    }

    protected function printResult(InputInterface $input, OutputInterface $output, array $result): void
    {
        $io = new SymfonyStyle($input, $output);
        $io->table(['Type', 'Count'], [
            ['compare reference locale', $result['reference']],
            ['compare locales ',  $result['locales']],
            ['Total compare files',  $result['compared']],
            ['Total missing keys',  $result['missing']],
        ]);
    }
}
