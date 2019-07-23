<?php declare(strict_types = 1);

namespace App\Controller;

use App\Entity\KnowledgeBase\KnowledgeBase;
use App\Manager\KnowledgeBaseManagerInterface;
use FOS\RestBundle\Controller\Annotations\Route;
use Symfony\Component\HttpFoundation\Response;

class KnowledgeBaseController extends Controller
{
    /**
     * @Route(path="/kb", name="kb")
     */
    public function showAll(KnowledgeBaseManagerInterface $knowledgeBaseManager): Response
    {
        return $this->render('pages/knowledge_base.html.twig', [
            'articles' => $knowledgeBaseManager->getAll(),
        ]);
    }
}
