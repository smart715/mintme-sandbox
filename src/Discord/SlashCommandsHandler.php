<?php declare(strict_types = 1);

namespace App\Discord;

use App\Discord\SlashCommands\GetRoleCommand;
use App\Discord\SlashCommands\SlashCommandInterface;
use Discord\InteractionResponseType;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

class SlashCommandsHandler implements SlashCommandsHandlerInterface, ServiceSubscriberInterface
{
    private ContainerInterface $container;

    public function __construct(
        ContainerInterface $container
    ) {
        $this->container = $container;
    }

    public static function getSubscribedServices(): array
    {
        return [
            GetRoleCommand::getName() => GetRoleCommand::class,
        ];
    }

    public function handleInteraction(array $params): array
    {
        $commandName = $params['data']['name'];

        if ($this->container->has($commandName)) {
            /** @var SlashCommandInterface $command */
            $command = $this->container->get($commandName);

            return $command->handleInteraction($params);
        }

        return [
            'type' => InteractionResponseType::CHANNEL_MESSAGE_WITH_SOURCE,
            'data' => [
                'content' => 'unknown command',
            ],
        ];
    }
}
