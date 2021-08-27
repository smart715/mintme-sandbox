<?php declare(strict_types = 1);

namespace App\Utils\ServiceInfo;

use App\Utils\ServiceInfo\Model\ServiceInfo;

class ServiceInfoDirector implements ServiceInfoDirectorInterface
{
    /** @var ServiceInfoBuilderInterface */
    public $infoBuilder;

    public function __construct(ServiceInfoBuilderInterface $infoBuilder)
    {
        $this->infoBuilder = $infoBuilder;
    }

    public function build(): ServiceInfo
    {
        $this->infoBuilder->addMintmeTokenInfo();
        $this->infoBuilder->addGitInfo();
        $this->infoBuilder->addConsumersInfo();
        $this->infoBuilder->addServicesStatus();

        return $this->infoBuilder->getServiceInfo();
    }
}
