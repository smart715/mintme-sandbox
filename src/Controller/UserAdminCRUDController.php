<?php declare(strict_types = 1);

namespace App\Controller;

use Sonata\AdminBundle\Controller\CRUDController as Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserAdminCRUDController extends Controller
{
    /**
     * @param mixed $id
     * @return RedirectResponse
     * @throws NotFoundHttpException
     */
    public function resetPasswordAction($id): RedirectResponse
    {
        return new RedirectResponse($this->admin->generateUrl('list'));
    }
}
