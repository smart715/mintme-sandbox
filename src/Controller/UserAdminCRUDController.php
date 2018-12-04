<?php

namespace App\Controller;

use App\Entity\User;
use App\Manager\SiteManagerInterface;
use App\Manager\UserManagerInterface;
use FOS\UserBundle\Model\UserInterface;
use Knp\Menu\Renderer\TwigRenderer;
use ReflectionClass;
use Sonata\AdminBundle\Controller\CRUDController as Controller;
use Sonata\AdminBundle\Exception\ModelManagerException;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
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
