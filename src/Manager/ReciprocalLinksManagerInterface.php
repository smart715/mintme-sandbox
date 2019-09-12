<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\KnowledgeBase\KnowledgeBase;

interface ReciprocalLinksManagerInterface
{
    /**
     * @return KnowledgeBase[]
     */
    public function getAll(): array;
}
