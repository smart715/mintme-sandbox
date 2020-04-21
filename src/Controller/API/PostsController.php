<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Entity\Post;
use App\Exception\ApiNotFoundException;
use App\Form\PostType;
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

    public function __construct(
        TokenManagerInterface $tokenManager,
        EntityManagerInterface $entityManager
    ) {
        $this->tokenManager = $tokenManager;
        $this->entityManager = $entityManager;
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

        $form = $this->createForm(PostType::class, $post, ['csrf_protection' => false]);

        $form->submit($request->all());

        if (!$form->isValid()) {
            return $this->view($form, Response::HTTP_BAD_REQUEST);
        }

        $post->setToken($token);

        $this->entityManager->persist($post);
        $this->entityManager->flush();

        return $this->view([], Response::HTTP_OK);
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
}
