<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Token\DiscordConfig;
use Doctrine\ORM\EntityManagerInterface;

class DiscordConfigManager implements DiscordConfigManagerInterface
{
    private EntityManagerInterface $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
    }

    public function disable(DiscordConfig $config): void
    {
        $config->disable();
        $this->entityManager->persist($config);

        $this->entityManager->flush();
    }
}
