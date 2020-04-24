<?php declare(strict_types = 1);

namespace App\Controller;

use App\Exception\NotFoundPostException;
use App\Manager\PostManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @Route("/post")
 */
class PostController extends Controller
{
    /** @var PostManagerInterface */
    private $postManager;

    public function __construct(
        NormalizerInterface $normalizer,
        PostManagerInterface $postManager
    ) {
        parent::__construct($normalizer);
        $this->postManager = $postManager;
    }

    /**
     * @Route("/edit/{id}", name="edit_post_page", options={"expose"=true})
     */
    public function edit(int $id): Response
    {
        $post = $this->postManager->getById($id);

        if (!$post) {
            throw new NotFoundPostException();
        }

        $this->denyAccessUnlessGranted('edit', $post);

        return $this->render('pages/edit_post.html.twig', [
            'post' => $this->normalize($post),
        ]);
    }
}
