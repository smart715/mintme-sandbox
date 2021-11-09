<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\ReciprocalLinks;

interface ReciprocalLinksManagerInterface
{
    /**
     * @return ReciprocalLinks[]
     */
    public function getAll(): array;
}
