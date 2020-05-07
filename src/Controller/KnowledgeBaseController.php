<?php declare(strict_types = 1);

namespace App\Controller;

use App\Exception\NotFoundKnowledgeBaseException;
use App\Manager\KnowledgeBaseManagerInterface;
use FOS\RestBundle\Controller\Annotations\Route;
use Symfony\Component\HttpFoundation\Response;

class KnowledgeBaseController extends Controller
{
    /**
     * @Route(
     *     path="/kb",
     *     name="kb",
     *     options={"expose"=true,
     *          "sitemap" = true,
     *          "2fa_progress"=false
     *     }
     * )
     */
    public function showAll(KnowledgeBaseManagerInterface $kbManager): Response
    {
        return $this->render('pages/knowledge_base.html.twig', [
            'knowledgeBases' => $kbManager->getAll(),
        ]);
    }

    /**
     * @Route(path="/kb/{url}", name="kb_show", methods={"GET"})
     */
    public function show(string $url, KnowledgeBaseManagerInterface $kbManager): Response
    {
        $article = $kbManager->getByUrl($url);

        if (!$article) {
            throw new NotFoundKnowledgeBaseException();
        }

        return $this->render('pages/knowledge_base_show.html.twig', [
           'article' => $article,
        ]);
    }
}
