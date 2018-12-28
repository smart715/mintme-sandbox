<?php

namespace App\Controller\API;

use App\Manager\UserManager;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\View\View;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/** @Rest\Route("/api/user") */

class UserAPIController extends FOSRestController
{
    /** @var UserManager */
    private $userManager;

    /** @var NormalizerInterface */
    private $normalizer;

    public function __construct(UserManager $userManager, NormalizerInterface $normalizer)
    {
        $this->userManager = $userManager;
        $this->normalizer = $normalizer;
    }

    /**
     *  @Rest\Get("/find/{id}/", name="find_by_id", options={"expose"=true})
     *  @Rest\View()
     */
    public function findById(int $id): View
    {
        return $this->view(
            $this->normalizer->normalize($this->userManager->find($id)->getProfile(), null, [
                'groups' => [ 'API' ],
            ])
        );
    }
}
