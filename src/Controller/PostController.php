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
     * @Route("/{id<\d+>}", name="show_post", options={"expose"=true})
     */
    public function show(int $id): Response
    {
       	if(!empty($id)){
		 $post = $this->postManager->getById($id);
	} else {
	$post = 0;
	}

        if (!$post) {
            throw new NotFoundPostException();
        }

        return $this->render('pages/show_post.html.twig', [
            'post' => $this->normalize($post),
            'showEdit' => $this->isGranted('edit', $post) ? 'true' : 'false',
            'comments' => $this->normalize($post->getComments()),
        ]);
    }

    /**
     * @Route("/edit/{id<\d+>}", name="edit_post_page", options={"expose"=true})
     */
    public function edit(int $id): Response
    {
        $post = $this->postManager->getById($id);

        if (!$post) {
            throw new NotFoundPostException();
        }

        $this->denyAccessUnlessGranted('edit', $post);

        /** @var array $post */
        $post = $this->normalize($post);

        // This is safe to do here, because we know it's going to be shown on an textarea
        // shouldn't be done anywhere else
        $post['content'] = html_entity_decode($post['content']);

        return $this->render('pages/edit_post.html.twig', [
            'post' => $post,
        ]);
    }
}
