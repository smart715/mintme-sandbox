<?php declare(strict_types = 1);

namespace App\Services\BackendService;

use Symfony\Component\HttpFoundation\Request;

interface BackendContainerBuilderInterface
{
    public function createContainer(Request $request): void;

    public function deleteContainer(Request $request): ?string;

    public function getStatusContainer(Request $request): ?int;
}
