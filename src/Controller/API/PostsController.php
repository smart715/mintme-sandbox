<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\User;
use App\Events\PostEvent;
use App\Events\TokenEvents;
use App\Exception\ApiBadRequestException;
use App\Exception\ApiNotFoundException;
use App\Exception\InvalidTwitterTokenException;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Form\CommentType;
use App\Form\PostType;
use App\Mailer\MailerInterface;
use App\Manager\PostManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Manager\TwitterManagerInterface;
use App\Manager\UserNotificationManagerInterface;
use App\Notifications\Strategy\NotificationContext;
use App\Notifications\Strategy\TokenPostNotificationStrategy;
use App\Utils\NotificationTypes;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Rest\Route("/api/posts")
 */
class PostsController extends AbstractFOSRestController
{

    public const TWITTER_INVALID_TOKEN_ERROR = 89;
    private TokenManagerInterface $tokenManager;
    private EntityManagerInterface $entityManager;
    private PostManagerInterface $postManager;
    private TranslatorInterface $translator;
    private LoggerInterface $logger;
    private UserNotificationManagerInterface $userNotificationManager;
    private MailerInterface $mailer;
    private AsciiSlugger $slugger;
    private TwitterManagerInterface $twitterManager;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        TokenManagerInterface $tokenManager,
        EntityManagerInterface $entityManager,
        PostManagerInterface $postManager,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        UserNotificationManagerInterface $userNotificationManager,
        MailerInterface $mailer,
        TwitterManagerInterface $twitterManager,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->tokenManager = $tokenManager;
        $this->entityManager = $entityManager;
        $this->postManager = $postManager;
        $this->translator = $translator;
        $this->logger = $logger;
        $this->userNotificationManager = $userNotificationManager;
        $this->mailer = $mailer;
        $this->slugger = new AsciiSlugger();
        $this->twitterManager = $twitterManager;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @Rest\View()
     * @Rest\Post("/create/{tokenName}", name="create_post", options={"expose"=true})
     * @Rest\RequestParam(name="content", nullable=false)
     * @Rest\RequestParam(name="amount", nullable=false)
     * @Rest\RequestParam(name="title", nullable=false)
     * @Rest\RequestParam(name="shareReward", nullable=false)
     */
    public function create(string $tokenName, ParamFetcherInterface $request): View
    {
        $user = $this->getUser();

        if (!$user) {
            throw new AccessDeniedHttpException();
        }

        $token = $this->tokenManager->getOwnTokenByName($tokenName);

        if (!$token) {
            throw new ApiNotFoundException($this->translator->trans('post_form.backend.no_token'));
        }

        $post = new Post();
        $post->setToken($token);

        $response = $this->handlePostForm($post, $request, 'Post created.', true);

        /** @psalm-suppress TooManyArguments */
        $this->eventDispatcher->dispatch(new PostEvent($post), TokenEvents::POST_CREATED);

        return $response;
    }

    /**
     * @Rest\View()
     * @Rest\Post("/edit/{id<\d+>}", name="edit_post", options={"expose"=true})
     * @Rest\RequestParam(name="content", nullable=false)
     * @Rest\RequestParam(name="amount", nullable=false)
     * @Rest\RequestParam(name="title", nullable=false)
     * @Rest\RequestParam(name="shareReward", nullable=false)
     */
    public function edit(ParamFetcherInterface $request, int $id): View
    {
        $user = $this->getUser();

        if (!$user) {
            throw new AccessDeniedHttpException();
        }

        $post = $this->postManager->getById($id);

        if (!$post) {
            throw new ApiNotFoundException($this->translator->trans('post.not_found'));
        }

        $this->denyAccessUnlessGranted('edit', $post);

        return $this->handlePostForm($post, $request, $this->translator->trans('post.edited'));
    }

    /**
     * @Rest\View()
     * @Rest\Get("/list/{tokenName}", name="list_posts", options={"expose"=true})
     * @param string $tokenName
     * @return View
     * @throws ApiNotFoundException
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
            throw new ApiNotFoundException($this->translator->trans('post.not_found'));
        }

        $this->denyAccessUnlessGranted('edit', $post);

        $this->entityManager->remove($post);
        $this->entityManager->flush();

        return $this->view(['message' => $this->translator->trans('post.deleted')], Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Get("/{id<\d+>}/comments", name="get_post_comments", options={"expose"=true})
     */
    public function getComments(int $id): View
    {
        $post = $this->postManager->getById($id);

        if (!$post) {
            throw new ApiNotFoundException($this->translator->trans('post.not_found'));
        }

        return $this->view($post->getComments(), Response::HTTP_OK);
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
            throw new ApiNotFoundException($this->translator->trans('post.not_found'));
        }

        $comment = new Comment();
        $comment->setPost($post)->setAuthor($user);

        return $this->handleCommentForm($comment, $request, $this->translator->trans('comment.created'));
    }

    /**
     * @Rest\View()
     * @Rest\Post("/comments/delete/{commentId<\d+>}", name="delete_comment", options={"expose"=true})
     */
    public function deleteComment(Comment $comment): View
    {
        $this->denyAccessUnlessGranted('delete', $comment);
        $this->entityManager->remove($comment);
        $this->entityManager->flush();
        $message = $this->translator->trans('post.comment.deleted');

        return $this->view(['message' => $message], Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/comments/edit/{commentId<\d+>}", name="edit_comment", options={"expose"=true})
     * @Rest\RequestParam(name="content", nullable=false)
     */
    public function editComment(ParamFetcherInterface $request, Comment $comment): View
    {
        $user = $this->getUser();

        if (!$user) {
            throw new AccessDeniedHttpException();
        }

        $this->denyAccessUnlessGranted('edit', $comment);

        return $this->handleCommentForm($comment, $request, $this->translator->trans('comment.edited'));
    }

    /**
     * @Rest\View()
     * @Rest\Post("/comments/like/{commentId<\d+>}", name="like_comment", options={"expose"=true})
     */
    public function likeComment(Comment $comment): View
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            throw new AccessDeniedHttpException();
        }

        $isAlreadyliked = $comment->getLikedBy($user);

        if ($isAlreadyliked) {
            $comment->removeLike($user);
            $this->entityManager->persist($comment);

            $this->entityManager->flush();

            return $this->view(['message' => $this->translator->trans('like.removed'), Response::HTTP_OK]);
        }

        $comment->addLike($user);
        $this->entityManager->persist($comment);
        $this->entityManager->flush();

        return $this->view(['message' => $this->translator->trans('comment.liked'), Response::HTTP_OK]);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/{id<\d+>}/share", name="share_post", options={"expose"=true})
     */
    public function sharePost(int $id, BalanceHandlerInterface $balanceHandler): View
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            throw new AccessDeniedHttpException();
        }

        $post = $this->postManager->getById($id);

        if (!$post) {
            throw new ApiNotFoundException($this->translator->trans('post.not_found'));
        }

        if ($post->isUserAlreadyRewarded($user)) {
            throw new ApiBadRequestException('already rewarded');
        }

        if ($user->getId() === $post->getToken()->getOwner()->getId()) {
            throw new ApiBadRequestException();
        }

        $url = $this->generateUrl('show_post', ['id' => $post->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        $message = $this->translator->trans('post.share.message', [
            '%title%' => $post->getTitle(),
            '%url%' => $url,
        ]);

        try {
            $this->twitterManager->sendTweet($user, $message);
        } catch (InvalidTwitterTokenException $e) {
            throw new ApiBadRequestException($e->getMessage());
        } catch (\Throwable $e) {
            throw new \Exception($this->translator->trans('api.something_went_wrong'));
        }

        $token = $post->getToken();
        $tokenOwner = $token->getOwner();

        $tokenOwnerBalance = $balanceHandler->exchangeBalance($tokenOwner, $token);

        $reward = $post->getShareReward();

        if ($tokenOwnerBalance->lessThan($reward)) {
            return $this->view(['message' => 'not enough funds'], Response::HTTP_CONFLICT);
        }

        try {
            $balanceHandler->withdraw($tokenOwner, $token, $reward);
            $balanceHandler->deposit($user, $token, $reward);
            $post->addRewardedUser($user);
            $this->entityManager->persist($post);
            $this->entityManager->flush();
        } catch (\Throwable $e) {
            $this->logger->error("Failed to give reward for sharing post: {$e->getMessage()}");

            throw new \Exception($this->translator->trans('api.something_went_wrong'));
        }

        return $this->view(['message' => $this->translator->trans('api.success')], Response::HTTP_OK);
    }

    private function handlePostForm(
        Post $post,
        ParamFetcherInterface $request,
        string $message,
        bool $newPost = false
    ): View {
        $form = $this->createForm(PostType::class, $post, ['csrf_protection' => false]);

        $form->submit($request->all());

        if (!$form->isValid()) {
            return $this->view($form, Response::HTTP_BAD_REQUEST);
        }

        $slug = $baseSlug = $this->slugger->slug($post->getTitle())->toString();

        for ($i = 2; $this->postManager->getBySlug($slug); $i++) {
            $slug = $baseSlug.'-'.$i;
        }

        $post->setSlug($slug);

        $this->entityManager->persist($post);
        $this->entityManager->flush();

        if ($newPost) {
            /** @var User $user */
            $user = $this->getUser();
            $token = $post->getToken();
            $notificationType = NotificationTypes::TOKEN_NEW_POST;
            $tokenUsers = $token->getUsers();
            $extraData = [
                'slug' => $post->getSlug(),
            ];

            $strategy = new TokenPostNotificationStrategy(
                $this->userNotificationManager,
                $this->mailer,
                $token,
                $extraData,
                $notificationType
            );
            $notificationContext = new NotificationContext($strategy);

            foreach ($tokenUsers as $tokenUser) {
                if ($tokenUser->getId() !== $user->getId()) {
                    $notificationContext->sendNotification($tokenUser);
                }
            }
        }

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
