<?php declare(strict_types = 1);

namespace App\Controller;

use FOS\RestBundle\Controller\Annotations\Route;
use Symfony\Component\HttpFoundation\Response;

class KnowledgeBaseController extends Controller
{

    /**
     * @Route(path="/kb", name="kb")
     */
    public function showAll(): Response
    {
        return $this->render('pages/knowledge_base.html.twig');
    }
}
