<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Token\DiscordConfig;
use App\Repository\DiscordConfigRepository;
use Doctrine\ORM\EntityManagerInterface;

class DiscordConfigManager implements DiscordConfigManagerInterface
{
    private EntityManagerInterface $entityManager;
    private DiscordConfigRepository $repository;

    public function __construct(
        EntityManagerInterface $entityManager,
        DiscordConfigRepository $repository
    ) {
        $this->entityManager = $entityManager;
        $this->repository = $repository;
    }

    public function disable(DiscordConfig $config): void
    {
        $config->disable();
        $this->entityManager->persist($config);

        $this->entityManager->flush();
    }

    public function findByGuildId(int $guildId): ?DiscordConfig
    {
        /** @var DiscordConfig|null $discordConfig */
        $discordConfig = $this->repository->findOneBy(['guildId' => $guildId]);

        return $discordConfig;
    }
}
