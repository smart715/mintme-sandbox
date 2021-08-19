<?php declare(strict_types = 1);

namespace App\Command;

use App\Communications\RestRpcInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\Request;

class CreateDiscordCommand extends Command
{
    private RestRpcInterface $guzzleRestWrapper;
    private string $clientId;

    public function __construct(
        RestRpcInterface $guzzleRestWrapper,
        string $clientId
    ) {
        $this->guzzleRestWrapper = $guzzleRestWrapper;
        $this->clientId = $clientId;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:create-discord-command')
            ->setDescription('Create discord slash command')
            ->addArgument('name', InputArgument::REQUIRED, 'Command name')
            ->addOption('description', 'd', InputOption::VALUE_OPTIONAL, 'Command description')
            ->addOption('guild_id', 'g', InputOption::VALUE_OPTIONAL, 'Id of guild')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var string $name */
        $name = $input->getArgument('name');

        /** @var string $guildId */
        $guildId = $input->getOption('guild_id') ?? '';

        /** @var string $description */
        $description = $input->getOption('description') ?? '';

        $this->createDiscordCommand($name, $description, $guildId);

        $io->success("Success.");

        return 0;
    }

    private function createDiscordCommand(string $name, string $description, string $guildId): void
    {
        $path = $this->clientId . ($guildId ? '/guilds/'.$guildId : '') . '/commands';

        $this->guzzleRestWrapper->send($path, Request::METHOD_POST, [
            'json' => [
                'name' => $name,
                'description' => $description,
            ],
        ]);
    }
}
