<?php declare(strict_types = 1);

namespace App\Services\BackendService;

interface BackendContainerBuilderInterface
{
    public function createContainer(string $branch): ?string;

    public function deleteContainer(string $branch): ?string;

    public function getStatusContainer(String $branch): void;
}
