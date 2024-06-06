<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Validator\Constraints\NoBadWordsValidator;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Rest\Route("/api/profanity")
 */
class ProfanityController extends AbstractFOSRestController
{
    /**
     * @Rest\View()
     * @Rest\GET("/getCensorConfig", name="get_censor_config", options={"expose"=true})
     */
    public function getCensorConfig(NoBadWordsValidator $validator): View
    {
        $validator->setUpCensor();

        return $this->view([
            'censorChecks' => $validator->getCensorChecks(),
            'whitelist' => $validator->getWhitelistedWords(),
        ], Response::HTTP_OK);
    }
}
