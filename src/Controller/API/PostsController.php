<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Entity\Post;
use App\Exception\ApiNotFoundException;
use App\Form\PostType;
use App\Manager\PostManagerInterface;
use App\Manager\TokenManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @Rest\Route("/api/posts")
 */
class PostsController extends AbstractFOSRestController
{
    /** @var TokenManagerInterface */
    private $tokenManager;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var PostManagerInterface */
    private $postManager;

    public function __construct(
        TokenManagerInterface $tokenManager,
        EntityManagerInterface $entityManager,
        PostManagerInterface $postManager
    ) {
        $this->tokenManager = $tokenManager;
        $this->entityManager = $entityManager;
        $this->postManager = $postManager;
    }

    /**
     * @Rest\View()
     * @Rest\Post("/create", name="create_post", options={"expose"=true})
     * @Rest\RequestParam(name="content", nullable=false)
     * @Rest\RequestParam(name="amount", nullable=false)
     */
    public function create(ParamFetcherInterface $request): View
    {
        $user = $this->getUser();

        if (!$user) {
            throw new AccessDeniedHttpException();
        }

        $token = $this->tokenManager->getOwnToken();

        if (!$token) {
            throw new ApiNotFoundException('Current user has not created a token');
        }

        $post = new Post();
        $post->setToken($token);

        return $this->handlePostForm($post, $request, 'Post created.');
    }

    /**
     * @Rest\View()
     * @Rest\Post("/edit/{id<\d+>}", name="edit_post", options={"expose"=true})
     * @Rest\RequestParam(name="content", nullable=false)
     * @Rest\RequestParam(name="amount", nullable=false)
     */
    public function edit(ParamFetcherInterface $request, int $id): View
    {
        $user = $this->getUser();

        if (!$user) {
            throw new AccessDeniedHttpException();
        }

        $post = $this->postManager->getById($id);

        if (!$post) {
            throw new ApiNotFoundException("Post not found");
        }

        $this->denyAccessUnlessGranted('edit', $post);

        return $this->handlePostForm($post, $request, 'Post edited.');
    }

    /**
     * @Rest\View()
     * @Rest\Get("/list/{tokenName}", name="list_posts", options={"expose"=true})
     */
    public function list(string $tokenName): View
    {
        $token = $this->tokenManager->findByName($tokenName);

        if (!$token) {
            throw new ApiNotFoundException();
        }

        return $this->view($token->getPosts(), Response::HTTP_OK);
    }

    private function handlePostForm(Post $post, ParamFetcherInterface $request, string $message): View
    {
        $form = $this->createForm(PostType::class, $post, ['csrf_protection' => false]);

        $form->submit($request->all());

        if (!$form->isValid()) {
            return $this->view($form, Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($post);
        $this->entityManager->flush();

        return $this->view(["message" => $message], Response::HTTP_OK);
    }
}
