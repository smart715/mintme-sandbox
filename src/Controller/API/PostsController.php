<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Entity\Comment;
use App\Entity\Like;
use App\Entity\Post;
use App\Entity\User;
use App\Exception\ApiNotFoundException;
use App\Form\CommentType;
use App\Form\PostType;
use App\Manager\CommentManagerInterface;
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

    /** @var CommentManagerInterface */
    private $commentManager;

    public function __construct(
        TokenManagerInterface $tokenManager,
        EntityManagerInterface $entityManager,
        PostManagerInterface $postManager,
        CommentManagerInterface $commentManager
    ) {
        $this->tokenManager = $tokenManager;
        $this->entityManager = $entityManager;
        $this->postManager = $postManager;
        $this->commentManager = $commentManager;
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

    /**
     * @Rest\View()
     * @Rest\Post("/delete/{id<\d+>}", name="delete_post", options={"expose"=true})
     */
    public function delete(int $id): View
    {
        $post = $this->postManager->getById($id);

        if (!$post) {
            throw new ApiNotFoundException("Post not found");
        }

        $this->denyAccessUnlessGranted('edit', $post);

        $this->entityManager->remove($post);
        $this->entityManager->flush();

        return $this->view(['message' => 'Post deleted.'], Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/{id<\d+>}/comments/add", name="add_comment", options={"expose"=true})
     * @Rest\RequestParam(name="content", nullable=false)
     */
    public function addComment(int $id, ParamFetcherInterface $request): View
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            throw new AccessDeniedHttpException();
        }

        $post = $this->postManager->getById($id);

        if (!$post) {
            throw new ApiNotFoundException('Post not found.');
        }

        $comment = new Comment();
        $comment->setPost($post)->setAuthor($user);

        return $this->handleCommentForm($comment, $request, 'Comment created.');
    }

    /**
     * @Rest\View()
     * @Rest\Post("/comments/delete/{id<\d+>}", name="delete_comment", options={"expose"=true})
     */
    public function deleteComment(int $id): View
    {
        $comment = $this->commentManager->getById($id);

        if (!$comment) {
            throw new ApiNotFoundException("Comment not found");
        }

        $this->denyAccessUnlessGranted('edit', $comment);

        $this->entityManager->remove($comment);
        $this->entityManager->flush();

        return $this->view(['message' => 'Comment deleted.'], Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/comments/edit/{id<\d+>}", name="edit_comment", options={"expose"=true})
     * @Rest\RequestParam(name="content", nullable=false)
     */
    public function editComment(ParamFetcherInterface $request, int $id): View
    {
        $user = $this->getUser();

        if (!$user) {
            throw new AccessDeniedHttpException();
        }

        $comment = $this->commentManager->getById($id);

        if (!$comment) {
            throw new ApiNotFoundException("Comment not found");
        }

        $this->denyAccessUnlessGranted('edit', $comment);

        return $this->handleCommentForm($comment, $request, 'Comment edited.');
    }

    /**
     * @Rest\View()
     * @Rest\Post("/comments/like/{id<\d+>}", name="like_comment", options={"expose"=true})
     */
    public function likeComment(int $id): View
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            throw new AccessDeniedHttpException();
        }

        $comment = $this->commentManager->getById($id);

        if (!$comment) {
            throw new ApiNotFoundException("Comment not found");
        }

        $like = $this->entityManager
            ->getRepository(Like::class)
            ->findOneBy(['user' => $user, 'comment' => $comment]);

        if ($like) {
            $this->entityManager->remove($like);
            $this->entityManager->flush();

            return $this->view(['message' => 'Like removed.', Response::HTTP_OK]);
        }

        $like = (new Like())->setComment($comment)->setUser($user);
        $this->entityManager->persist($like);
        $this->entityManager->flush();

        return $this->view(['message' => 'Liked comment.', Response::HTTP_OK]);
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

    private function handleCommentForm(Comment $comment, ParamFetcherInterface $request, string $message): View
    {
        $form = $this->createForm(CommentType::class, $comment, ['csrf_protection' => false]);

        $form->submit($request->all());

        if (!$form->isValid()) {
            return $this->view($form, Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($comment);
        $this->entityManager->flush();

        return $this->view(["message" => $message, "comment" => $comment], Response::HTTP_OK);
    }
}
