<?php declare(strict_types = 1);

namespace App\Controller;

use App\Entity\Token\Token;
use App\Entity\User;
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
        $post = $this->postManager->getById($id);

        if (!$post) {
            throw new NotFoundPostException();
        }

        $slug = $post->getSlug();

        if ($slug) {
            return $this->redirectToRoute('new_show_post', [
                'name' => $post->getToken()->getName(),
                'slug' => $slug,
            ]);
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

        /** @var array $serializedPost */
        $serializedPost = $this->normalize($post);

        // This is safe to do here, because we know it's going to be shown on an textarea
        // shouldn't be done anywhere else
        $serializedPost['content'] = html_entity_decode($serializedPost['content']);

        $decimals = $post->getToken()->getDecimals();

        return $this->render('pages/edit_post.html.twig', [
            'post' => $serializedPost,
            'decimals' => null === $decimals || $decimals > Token::TOKEN_SUBUNIT
                ? Token::TOKEN_SUBUNIT
                : $decimals,
        ]);
    }

    /**
     * @Route("/home", name="show_user_home")
     */
    public function home(): Response
    {
        return $this->render('pages/show_user_home.html.twig');
    }
}
