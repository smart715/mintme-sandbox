<?php declare(strict_types = 1);

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;

class SetParameterCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'app:set-parameter';
    private string $parametersPath;

    public function __construct(string $parametersPath)
    {
        parent::__construct();

        $this->parametersPath = $parametersPath;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Set a parameter in parameters.yaml file')
            ->addArgument('name', InputArgument::REQUIRED, 'Parameter name')
            ->addOption('value', 'val', InputOption::VALUE_REQUIRED, 'Parameter value')
            ->addOption('remove', null, null, 'Remove value from parameters in form of table');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var string|null $name */
        $name = $input->getArgument('name');

        /** @var string|null $value */
        $value = $input->getOption('value');

        /** @var bool|null $remove */
        $remove = $input->getOption('remove');

        $fullParametersFile = Yaml::parseFile($this->parametersPath);
        $parameters = &$fullParametersFile['parameters'];

        if (null === $value) {
            throw new \RuntimeException('The "--value" option must be provided');
        }

        // Parse the parameter name to retrieve the nested array keys
        $keys = str_contains($name, '.')
            ? explode('.', $name)
            : [$name];

        $this->setParameter($keys, $value, $parameters, $remove, false);

        // 99 is used to keep the correct indentation of the file
        \file_put_contents($this->parametersPath, Yaml::dump($fullParametersFile, 99));

        if ($remove) {
            $io->success(sprintf('"%s" was removed from "%s"', var_export($value, true), $name));
        } else {
            $io->success(sprintf('Parameter "%s" set to "%s"', $name, var_export($value, true)));
        }

        return 0;
    }

    /**
     * @param array<string> $keys
     * @param string $value
     * @param array $parameters
     * @param bool $remove
     * @param bool $firstCall To check if it is first call on the full parameters array
     */
    private function setParameter(array $keys, string $value, array &$parameters, bool $remove, bool $firstCall = false): void
    {
        $name = array_shift($keys);

        if (!$name) {
            throw new \RuntimeException('Parameter keys input is empty');
        }

        $parameterValue = &$parameters[$name] ?? null;

        if (!isset($parameterValue) && !$firstCall) {
            throw new \RuntimeException(sprintf('Parameter "%s" does not exist in parameters.yaml', $name));
        }

        // Convert the type of the new value
        if (!is_array($parameterValue)) {
            if ($remove) {
                throw new \RuntimeException(sprintf('You cannot remove "%s" from "%s" parameter because it\'s not an array', $value, $name));
            }

            $parameters[$name] = $this->convertType($value, $this->guessType($parameters, $parameterValue, $value));

            return;
        }

        if ($this->isArrayList($parameterValue)) {
            if (0 < count($keys)) {
                throw new \RuntimeException(
                    sprintf('Parameter "%s" is an array. It does not support subparameters:', $name) . json_encode($keys)
                );
            }

            if ($remove) {
                $key_index = array_search($value, $parameterValue);

                if (false !== $key_index) {
                    array_splice($parameterValue, (int)$key_index, 1);
                } else {
                    throw new \RuntimeException(sprintf('You cannot remove "%s" from "%s" parameter because it doesn\'t exist', $value, $name));
                }

                return;
            }

            $value = $this->convertType($value, $this->guessType($parameterValue));

            if (in_array($value, $parameterValue)) {
                throw new \RuntimeException(sprintf('Parameter "%s" already has "%s" value', $name, $value));
            }

            $parameterValue[] = $value;

            return;
        }

        // At this point, it is an associative array

        // it goes to the next sub parameter
        // $keys first element was already removed
        $this->setParameter($keys, $value, $parameterValue, $remove, true);
    }

    /**
     * @return mixed
     */
    private function convertType(string $value, string $type)
    {
        if (!in_array($type, ['boolean', 'integer', 'double', 'string', 'float'])) {
            throw new \RuntimeException("Guessed type is not supported: $type");
        }

        if (in_array($type, ['integer', 'double', 'float']) && !is_numeric($value)) {
            throw new \RuntimeException('Parameter to edit must be numeric');
        }

        if ("boolean" === $type) {
            if (!in_array(strtolower($value), ['true', 'false'], true)) {
                throw new \RuntimeException('Parameter to edit must be boolean');
            }

            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        settype($value, $type);

        return $value;
    }

    /**
     * @param string|int|bool|double $oldValue
     * @param string|int|bool|double $newValue
     */
    private function guessType(array $parameters, $oldValue = null, $newValue = null): string
    {
        if (null !== $oldValue) {
            // sets the newValue type to the correct one instead of the one retrieved from oldValue
            if (is_numeric($newValue) && is_numeric($oldValue)) {
                return (float)$newValue == (int)$newValue
                    ? 'integer'
                    : 'float';
            }

            return gettype($oldValue);
        }

        // Getting the first value to compare it with
        $testParam = array_values($parameters)[0] ?? null;

        return !$testParam
            ? 'string'
            : gettype($testParam);
    }

    /**
     * Checks if array is a sequential
     *
     * array_is_list(["0" => 'a', "1" => 'b', "2" => 'c']) // true
     * array_is_list(["1" => 'a', "0" => 'b', "2" => 'c']) // false
     * array_is_list(["a" => 'a', "b" => 'b', "c" => 'c']) // false
     */
    private function isArrayList(array $arr): bool
    {
        if ([] === $arr) {
            return true;
        }

        return array_keys($arr) === range(0, count($arr) - 1);
    }
}
