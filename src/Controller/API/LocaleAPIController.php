<?php declare(strict_types = 1);

namespace App\Controller\API;

use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @Rest\Route("/api/locale")
 */
class LocaleAPIController extends APIController
{
    /**
     * @Rest\View()
     * @Rest\Post(
     *     "/change/{locale}",
     *     name="change_locale",
     *     requirements={"locale" = "%translation_requirements%"},
     *     options={"expose"=true}
     *     )
     */
    public function changeLocale(string $locale, SessionInterface $session): View
    {
        $session->set('_locale', $locale);

        return $this->view(['locale' => $locale], Response::HTTP_OK);
    }
}
