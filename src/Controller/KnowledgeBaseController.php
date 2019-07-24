<?php declare(strict_types = 1);

namespace App\Controller;

use App\Manager\KnowledgeBaseManagerInterface;
use FOS\RestBundle\Controller\Annotations\Route;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

class KnowledgeBaseController extends Controller
{
    /**
     * @Route(path="/kb", name="kb")
     */
    public function showAll(KnowledgeBaseManagerInterface $kbManager): Response
    {
        return $this->render('pages/knowledge_base.html.twig', [
            'articles' => $kbManager->getAll(),
        ]);
    }

    /**
     * @Route(path="/kb/{url}", name="kb_show", methods={"GET"})
     */
    public function show(string $url, KnowledgeBaseManagerInterface $kbManager): Response
    {
        $article = $kbManager->getByUrl($url);

        if (!$article) {
            throw new InvalidArgumentException();
        }

        return $this->render('pages/knowledge_base_show.html.twig', [
           'article' => $article,
        ]);
    }
}
