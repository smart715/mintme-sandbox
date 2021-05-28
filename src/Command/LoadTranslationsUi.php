<?php declare(strict_types = 1);

namespace App\Command;

use RecursiveIteratorIterator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Yaml\Yaml;

class LoadTranslationsUi extends Command
{
    private string $translationsPath;
    private string $jsPath;
    private string $twigPath;
    private string $filepath;
    private string $extraFilepath;
    protected EngineInterface $twigEngine;

    public function __construct(
        EngineInterface $twigEngine,
        ParameterBagInterface $params
    ) {
        $this->filepath = $params->get('ui_trans_keys_filepath');
        $this->extraFilepath = $params->get('ui_extra_keys_filepath');

        $this->translationsPath = 'translations/messages.en.yml';
        $this->jsPath = 'assets/js';
        $this->twigPath = 'templates';
        $this->twigEngine = $twigEngine;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:load-translations-ui')
            ->setDescription('Update load translations for frontend')
            ->setHelp('Generate all the translation keys used in the frontend');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $translations = $this->getTranslations();
        $words = [];
        $paths = [$this->jsPath, $this->twigPath];

        foreach ($paths as $path) {
            $temp = $this->parseFiles($path);

            foreach ($temp as $word) {
                if (!in_array($word, $words)) {
                    try {
                        if (!in_array($word, $translations)) {
                            throw new \Exception($word. ' does not exist in '.$this->translationsPath);
                        } else {
                            $words[] = $word;
                        }
                    } catch (\Throwable $e) {
                        $output->writeln($e->getMessage());
                    }
                }
            }
        }

        sort($words);

        file_put_contents($this->filepath, json_encode($words));

        $output->writeln('Translations has been loaded');

        return 0;
    }

    private function parseFiles(string $path): array
    {
        $it = new RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
        $filesToCheck = [];

        while ($it->valid()) {
            if (!is_dir($it->key())) {
                $filesToCheck[] =  $it->key();
            }

            $it->next();
        }

        $words = file_exists($this->extraFilepath) ?
            json_decode(file_get_contents($this->extraFilepath) ?: '[]'):
            [];

        foreach ($filesToCheck as $filePath) {
            $file = file_get_contents($filePath, true);
            preg_match_all('/\$t\([\'"]([A-Za-z0-9\._]+)[\'"]/m', (string) $file, $matches);

            if ($matches) {
                foreach ($matches[1] as $word) {
                    if (!in_array($word, $words)) {
                        $words[] = $word;
                    }
                }
            }
        }

        return $words;
    }

    public function getTranslations(): array
    {
        return array_keys(Yaml::parseFile($this->translationsPath));
    }
}
