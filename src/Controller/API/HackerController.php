<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Utils\ServiceInfo\ServiceInfoDirectorInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @Rest\Route("/api/hacker-info")
 * @Security(expression="is_granted('hacker')")
 */
class HackerController extends APIController
{
    /**
     * @Rest\View()
     * @Rest\Get("/get", name="hacker_info", options={"expose"=true})
     */
    public function getStatusInfo(ServiceInfoDirectorInterface $serviceInfo): View
    {
        return $this->view($serviceInfo->build());
    }
}
