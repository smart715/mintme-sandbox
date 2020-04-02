<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\KnowledgeBase\KnowledgeBase;

interface KnowledgeBaseManagerInterface
{
    /**
     * @return KnowledgeBase[]
     */
    public function getAll(): array;

    public function getByUrl(string $shortUrl): ?KnowledgeBase;
}
