<?php declare(strict_types = 1);

namespace App\Utils\ServiceInfo;

use App\Utils\ServiceInfo\Model\ServiceInfo;

interface ServiceInfoBuilderInterface
{
    public function addMintmeTokenInfo(): void;

    public function addGitInfo(): void;

    public function addConsumersInfo(): void;

    public function addServicesStatus(): void;

    public function getServiceInfo(): ServiceInfo;
}
