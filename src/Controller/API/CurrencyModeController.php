<?php declare(strict_types = 1);

namespace App\Controller\API;

use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @Rest\Route("/api/currency-mode")
 */
class CurrencyModeController extends APIController
{
    /**
     * @Rest\View()
     * @Rest\Post(
     *     "/change/{mode}",
     *     name="change_currency_mode",
     *     options={"expose"=true}
     *     )
     * @param string $mode
     * @param SessionInterface $session
     * @return View
     */
    public function changeCurrencyMode(string $mode, SessionInterface $session): View
    {
        $session->set('_currency_mode', $mode);

        return $this->view(['_currency_mode' => $mode], Response::HTTP_ACCEPTED);
    }
}
