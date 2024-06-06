<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Manager\LinkedinManager;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Rest\Route("/api/linkedin")
 */
class LinkedinController extends AbstractFOSRestController
{
    /**
     * @Rest\Get("/callback", name="linkedin_callback", options={"expose"=true})
     */
    public function callback(Request $request, LinkedinManager $linkedinManager): Response
    {
        $code = $request->query->get('code');

        if (null !== $code) {
            $linkedinManager->setAccessToken($code);
        }

        $view = $this->renderView('pages/linkedin_callback.html.twig');

        return new Response($view, Response::HTTP_OK, ['Content-Type' => 'text/html']);
    }
}
