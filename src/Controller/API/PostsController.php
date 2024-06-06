<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Activity\ActivityTypes;
use App\Config\PostsConfig;
use App\Config\UserLimitsConfig;
use App\Controller\Traits\ViewOnlyTrait;
use App\Entity\Comment;
use App\Entity\CommentTip;
use App\Entity\Like;
use App\Entity\Post;
use App\Entity\PostUserShareReward;
use App\Entity\User;
use App\Events\Activity\TipTokenEventActivity;
use App\Events\CommentEvent;
use App\Events\PostEvent;
use App\Events\TokenEvents;
use App\Exception\ApiBadRequestException;
use App\Exception\ApiForbiddenException;
use App\Exception\ApiNotFoundException;
use App\Exception\ApiUnauthorizedException;
use App\Exception\InvalidTwitterTokenException;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Balance\BalanceTransactionBonusType;
use App\Form\CommentType;
use App\Form\PostType;
use App\Mailer\MailerInterface;
use App\Manager\CommentManagerInterface;
use App\Manager\CommentTipsManagerInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\HashtagManagerInterface;
use App\Manager\PostManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Manager\TwitterManagerInterface;
use App\Manager\UserActionManagerInterface;
use App\Manager\UserNotificationConfigManager;
use App\Manager\UserNotificationManagerInterface;
use App\Manager\UserTokenFollowManagerInterface;
use App\Notifications\Strategy\NotificationContext;
use App\Notifications\Strategy\TokenPostNotificationStrategy;
use App\Security\UserVoter;
use App\Services\TranslatorService\TranslatorInterface;
use App\Utils\ActionTypes;
use App\Utils\Converter\SlugConverterInterface;
use App\Utils\NotificationTypes;
use App\Utils\Policy\NotificationPolicyInterface;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @Rest\Route("/api/posts")
 */
class PostsController extends APIController
{

    public const TWITTER_INVALID_TOKEN_ERROR = 89;
    public const POSTS_LIST_BATCH_SIZE = 10;
    public const COMMENT_TIP_TYPE = 'comment_tip';

    protected TokenManagerInterface $tokenManager;
    private EntityManagerInterface $entityManager;
    private PostManagerInterface $postManager;
    private TranslatorInterface $translator;
    private LoggerInterface $logger;
    private UserNotificationConfigManager $userNotificationConfigManager;
    private UserNotificationManagerInterface $userNotificationManager;
    private MailerInterface $mailer;
    private SlugConverterInterface $slugger;
    private TwitterManagerInterface $twitterManager;
    private EventDispatcherInterface $eventDispatcher;
    private NotificationPolicyInterface $notificationPolicy;
    private UserLimitsConfig $userLimitsConfig;
    private UserActionManagerInterface $userActionManager;
    protected SessionInterface $session;
    private MoneyWrapperInterface $moneyWrapper;
    private UserTokenFollowManagerInterface $userTokenFollowManager;
    private CommentManagerInterface $commentManager;
    private HashtagManagerInterface $hashtagManager;

    use ViewOnlyTrait;

    public function __construct(
        TokenManagerInterface $tokenManager,
        EntityManagerInterface $entityManager,
        PostManagerInterface $postManager,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        UserNotificationConfigManager $userNotificationConfigManager,
        UserNotificationManagerInterface $userNotificationManager,
        MailerInterface $mailer,
        TwitterManagerInterface $twitterManager,
        EventDispatcherInterface $eventDispatcher,
        SlugConverterInterface $slugger,
        NotificationPolicyInterface $notificationPolicy,
        UserLimitsConfig $userLimitsConfig,
        UserActionManagerInterface $userActionManager,
        SessionInterface $session,
        UserTokenFollowManagerInterface $userTokenFollowManager,
        MoneyWrapperInterface $moneyWrapper,
        CommentManagerInterface $commentManager,
        HashtagManagerInterface $hashtagManager
    ) {
        $this->tokenManager = $tokenManager;
        $this->entityManager = $entityManager;
        $this->postManager = $postManager;
        $this->translator = $translator;
        $this->logger = $logger;
        $this->userNotificationConfigManager = $userNotificationConfigManager;
        $this->userNotificationManager = $userNotificationManager;
        $this->mailer = $mailer;
        $this->slugger = $slugger;
        $this->twitterManager = $twitterManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->notificationPolicy = $notificationPolicy;
        $this->userLimitsConfig = $userLimitsConfig;
        $this->userActionManager = $userActionManager;
        $this->session = $session;
        $this->userTokenFollowManager = $userTokenFollowManager;
        $this->moneyWrapper = $moneyWrapper;
        $this->commentManager = $commentManager;
        $this->hashtagManager = $hashtagManager;
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
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            throw new AccessDeniedHttpException();
        }

        // Validate amount and shareReward
        $this->validateProperties(null, [(string)$request->get('amount'), (string)$request->get('shareReward')]);

        $token = $this->tokenManager->getOwnTokenByName($tokenName);

        if (!$token) {
            throw new ApiNotFoundException($this->translator->trans('api.tokens.user_not_created_token'));
        } elseif ($token->isBlocked()) {
            throw new ApiForbiddenException($this->translator->trans('api.tokens.token_blocked', ['%tokenName%' => $token->getName()]));
        }

        $limitation = $this->userLimitsConfig->getMaxPostsLimit();

        $postsCount = $this->userActionManager->getCountByUserAtDate(
            $user,
            ActionTypes::CREATE_POST,
            new \DateTimeImmutable(date('Y-m-d'))
        );

        if ($postsCount >= $limitation) {
            throw new ApiForbiddenException($this->translator->trans('api.max_posts', ['%limit%' => $limitation]));
        }

        $post = new Post();
        $post->setToken($token);
        $this->userActionManager->createUserAction($user, ActionTypes::CREATE_POST);

        $response = $this->handlePostForm($post, $request, $this->translator->trans('post.created'), true);

        /** @psalm-suppress TooManyArguments */
        $this->eventDispatcher->dispatch(new PostEvent($post, ActivityTypes::NEW_POST), TokenEvents::POST_CREATED);

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
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

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
     * @Rest\QueryParam(name="offset", nullable=true)
     * @param string $tokenName
     * @return View
     * @throws ApiNotFoundException
     */
    public function list(ParamFetcherInterface $request, string $tokenName): View
    {
        $token = $this->tokenManager->findByName($tokenName);

        if (!$token) {
            throw new ApiNotFoundException();
        }

        return $this->view(
            $this->postManager->getActivePostsByToken(
                $token,
                intval($request->get('offset') ?? 0),
                self::POSTS_LIST_BATCH_SIZE
            ),
            Response::HTTP_OK
        );
    }

    /**
     * @Rest\View()
     * @Rest\Post("/delete/{id<\d+>}", name="delete_post", options={"expose"=true})
     */
    public function delete(int $id): View
    {
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

        $post = $this->postManager->getById($id);

        if (!$post) {
            throw new ApiNotFoundException($this->translator->trans('post.not_found'));
        }

        $this->denyAccessUnlessGranted('edit', $post);

        $post->setStatus(Post::STATUS_DELETED);
        $this->entityManager->persist($post);
        $this->entityManager->flush();

        return $this->view(['message' => $this->translator->trans('post.deleted')], Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/like/{id<\d+>}", name="like_post", options={"expose"=true})
     */
    public function likePost(int $id): View
    {
        $post = $this->postManager->getById($id);

        if (!$post) {
            throw new ApiNotFoundException($this->translator->trans('post.not_found'));
        }

        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            throw new AccessDeniedHttpException();
        }

        if ($post->isUserAlreadyLiked($user)) {
            $post->removeUserLike($user);
        } else {
            $limit = $this->userLimitsConfig->getMaxLikesLimit();

            $likesLimit = $this->userActionManager->getCountByUserAtDate(
                $user,
                ActionTypes::LIKE,
                new \DateTimeImmutable(date('Y-m-d'))
            );

            if ($likesLimit >= $limit) {
                throw new ApiForbiddenException($this->translator->trans('api.max_likes', ['%limit%' => $limit]));
            }

            $post->addUserLike($user);
            $this->userActionManager->createUserAction($user, ActionTypes::LIKE);
        }

        $this->entityManager->flush();

        if ($post->isUserAlreadyLiked($user)) {
            $this->eventDispatcher->dispatch(
                new PostEvent($post, ActivityTypes::POST_LIKE, $user),
                TokenEvents::POST_LIKED
            );
        }

        return $this->view([], Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Get("/{id<\d+>}/comments", name="get_post_comments", options={"expose"=true})
     */
    public function getComments(int $id, BalanceHandlerInterface $balanceHandler): View
    {
        $post = $this->postManager->getById($id);

        if (!$post) {
            throw new ApiNotFoundException($this->translator->trans('post.not_found'));
        }

        $canView = $this->isGranted('view', $post);

        return $this->view($canView ? $post->getComments() : [], Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/{id<\d+>}/comments/add", name="add_comment", options={"expose"=true})
     * @Rest\RequestParam(name="content", nullable=false)
     */
    public function addComment(
        int $id,
        ParamFetcherInterface $request,
        BalanceHandlerInterface $balanceHandler
    ): View {
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            throw new AccessDeniedHttpException();
        }
        
        $this->denyAccessUnlessGranted(UserVoter::ADD_COMMENT, $user);

        $commentsCount = $this->userActionManager->getCountByUserAtDate(
            $user,
            ActionTypes::CREATE_COMMENT,
            new \DateTimeImmutable(date('Y-m-d'))
        );
        $limitation = $this->userLimitsConfig->getMaxCommentsLimit();

        if ($commentsCount >= $limitation) {
            throw new ApiForbiddenException($this->translator->trans('api.max_comments', ['%limit%' => $limitation]));
        }

        $post = $this->postManager->getById($id);

        if (!$post) {
            throw new ApiNotFoundException($this->translator->trans('post.not_found'));
        }

        $token = $post->getToken();
        $tokenBalance = $balanceHandler->exchangeBalance($user, $token);
        $commentMinAmount = $this->moneyWrapper->parse(
            $token->getCommentMinAmount(),
            Symbols::TOK
        );
        $isOwner = $token->isOwner($this->tokenManager->getOwnTokens());

        if (!$isOwner && $tokenBalance->lessThan($commentMinAmount)) {
            return $this->view(
                ['message' => $this->translator->trans(
                    'comment.add_comment.min_amount',
                    [
                        '%amount%' => $token->getCommentMinAmount(),
                        '%currency%' => $token->getSymbol(),
                    ]
                )],
                Response::HTTP_CONFLICT
            );
        }

        $this->denyAccessUnlessGranted('interact', $post->getToken());
        $this->denyAccessUnlessGranted('view', $post);

        $comment = new Comment();
        $comment->setPost($post)->setAuthor($user);

        $this->userActionManager->createUserAction($user, ActionTypes::CREATE_COMMENT);

        return $this->handleCommentForm($comment, $request, $this->translator->trans('comment.created'), false, $user);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/comments/delete/{commentId<\d+>}", name="delete_comment", options={"expose"=true})
     */
    public function deleteComment(Comment $comment): View
    {
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

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
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

        $user = $this->getUser();

        if (!$user) {
            throw new AccessDeniedHttpException();
        }

        $this->denyAccessUnlessGranted('edit', $comment);

        return $this->handleCommentForm($comment, $request, $this->translator->trans('comment.edited'), true);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/comments/like/{commentId<\d+>}", name="like_comment", options={"expose"=true})
     */
    public function likeComment(Comment $comment): View
    {
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            throw new AccessDeniedHttpException();
        }

        $like = $comment->getLikedBy($user);

        if ($like) {
            $comment->removeLike($like);
            $this->entityManager->persist($comment);
            $this->entityManager->flush();

            return $this->view(['message' => $this->translator->trans('like.removed'), Response::HTTP_OK]);
        }

        $limit = $this->userLimitsConfig->getMaxLikesLimit();

        $likesLimit = $this->userActionManager->getCountByUserAtDate(
            $user,
            ActionTypes::LIKE,
            new \DateTimeImmutable(date('Y-m-d'))
        );

        if ($likesLimit >= $limit) {
            throw new ApiForbiddenException($this->translator->trans('api.max_likes', ['%limit%' => $limit]));
        }

        $like = new Like();
        $like = $like->setUser($user);

        $comment->addLike($like);

        $this->userActionManager->createUserAction($user, ActionTypes::LIKE);

        $this->entityManager->persist($comment);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(
            new CommentEvent($comment, ActivityTypes::COMMENT_LIKE, $user),
            TokenEvents::COMMENT_LIKE
        );

        return $this->view(['message' => $this->translator->trans('comment.liked'), Response::HTTP_OK]);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/comments/tip/{commentId<\d+>}", name="tip_comment", options={"expose"=true})
     * @Rest\RequestParam(name="tokenName", nullable=false)
     * @Rest\RequestParam(name="tipAmount", nullable=false)
    */
    public function tipComment(
        Comment $comment,
        BalanceHandlerInterface $balanceHandler,
        CryptoManagerInterface $cryptoManager,
        ParamFetcherInterface $request,
        MoneyWrapperInterface $moneyWrapper,
        PostsConfig $postsConfig,
        CommentTipsManagerInterface $commentsTipsManager
    ): View {
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            throw new AccessDeniedHttpException();
        }

        if ($comment->getAuthor() === $user) {
            throw new AccessDeniedHttpException();
        }

        if ($commentsTipsManager->getByUserAndComment($user, $comment)) {
            throw new ApiBadRequestException($this->translator->trans('api.comments.tips.already_tipped'));
        }

        $token = $user->getProfile()->getOwnTokenByName($request->get('tokenName'));

        if (!$token) {
            throw new AccessDeniedHttpException();
        }

        if (!$this->isGranted('edit', $token)) {
            throw new ApiUnauthorizedException($this->translator->trans('api.tokens.unauthorized'));
        }

        $tipAmount = $moneyWrapper->parse(
            (string)$request->get('tipAmount'),
            Symbols::TOK
        );

        $tipMinAmount = $moneyWrapper->parse((string)$postsConfig->getCommentsTipMinAmount(), Symbols::TOK);
        $tipMaxAmount = $moneyWrapper->parse((string)$postsConfig->getCommentsTipMaxAmount(), Symbols::TOK);

        if ($tipAmount->lessThan($tipMinAmount) || $tipAmount->greaterThan($tipMaxAmount)) {
            throw new ApiBadRequestException($this->translator->trans('api.comments.tips.wrong_amount'));
        }

        $tokenBalance = $balanceHandler->balance($user, $token)->getAvailable();
        $mintmeBalance = $balanceHandler->balance($user, $cryptoManager->findBySymbol(Symbols::WEB))->getAvailable();

        $tipCost = $moneyWrapper->parse((string)$postsConfig->getCommentsTipCost(), Symbols::WEB);

        if ($tokenBalance->lessThan($tipAmount)) {
            throw new ApiBadRequestException($this->translator->trans('comment.tip_modal.body.not_enough', [
                '%currency%' => $token->getName(),
            ]));
        }

        if ($mintmeBalance->lessThan($tipCost)) {
            throw new ApiBadRequestException($this->translator->trans('comment.tip_modal.body.not_enough', [
                '%currency%' => Symbols::MINTME,
            ]));
        }

        $author = $comment->getAuthor();

        $balanceHandler->beginTransaction();

        try {
            $balanceHandler->update(
                $user,
                $token,
                $tipAmount->negative(),
                self::COMMENT_TIP_TYPE
            );
            $balanceHandler->update(
                $user,
                $cryptoManager->findBySymbol(Symbols::WEB),
                $tipCost->negative(),
                self::COMMENT_TIP_TYPE
            );
            $balanceHandler->update($author, $token, $tipAmount, self::COMMENT_TIP_TYPE);

            $commentTip = (new CommentTip())
                ->setComment($comment)
                ->setUser($user)
                ->setToken($token)
                ->setAmount($tipAmount)
                ->setCurrency($tipAmount->getCurrency()->getCode());

            $commentFeeTip = (new CommentTip())
                ->setComment($comment)
                ->setUser($user)
                ->setToken($token)
                ->setAmount($tipCost)
                ->setCurrency($tipCost->getCurrency()->getCode())
                ->setTipType(CommentTip::FEE_TIP_TYPE);

            $this->entityManager->persist($commentTip);
            $this->entityManager->persist($commentFeeTip);
            $this->entityManager->flush();
            $this->eventDispatcher->dispatch(
                new TipTokenEventActivity($commentTip, ActivityTypes::TIP_RECEIVED),
                TipTokenEventActivity::NAME
            );

            return $this->view($commentTip, Response::HTTP_OK);
        } catch (\Throwable $e) {
            $balanceHandler->rollback();
            $this->logger->error("Failed to tip comment: {$e->getMessage()}");

            throw new ApiBadRequestException($this->translator->trans('api.something_went_wrong'));
        }
    }

    /**
     * @Rest\View()
     * @Rest\Post("/{id<\d+>}/share", name="share_post", options={"expose"=true})
     */
    public function sharePost(int $id, BalanceHandlerInterface $balanceHandler): View
    {
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            throw new AccessDeniedHttpException();
        }

        $post = $this->postManager->getById($id);

        if (!$post) {
            throw new ApiNotFoundException($this->translator->trans('post.not_found'));
        }

        if (Post::STATUS_DELETED === $post->getStatus()) {
            throw new ApiNotFoundException('post removed');
        }

        $this->denyAccessUnlessGranted('interact', $post->getToken());

        if ($post->isUserAlreadyRewarded($user)) {
            throw new ApiBadRequestException('already rewarded');
        }

        $postRewardCollectableDays = $this->getParameter('post_rewards_collectable_days');

        if ($post->getCreatedAt()->diff(new \DateTimeImmutable())->days >= $postRewardCollectableDays) {
            throw new ApiBadRequestException('post reward outdated');
        }

        $this->denyAccessUnlessGranted('collect-reward');

        if ($user->getId() === $post->getToken()->getOwner()->getId()) {
            throw new ApiBadRequestException();
        }

        $token = $post->getToken();
        $tokenOwner = $token->getOwner();
        $tokenOwnerBalance = $balanceHandler->exchangeBalance($tokenOwner, $token);

        $reward = $post->getShareReward();

        if ($tokenOwnerBalance->lessThan($reward)) {
            return $this->view(['message' => 'not enough funds'], Response::HTTP_CONFLICT);
        }

        $url = $this->generateUrl(
            'token_show_post',
            ['name' => $token->getName(), 'slug' => $post->getSlug()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

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

        try {
            $balanceHandler->beginTransaction();

            if (!$reward->isZero()) {
                $balanceHandler->withdraw($tokenOwner, $token, $reward);
                $balanceHandler->depositBonus($user, $token, $reward, BalanceTransactionBonusType::POST_SHARE);
            }

            $postUserShareReward = (new PostUserShareReward())
                ->setPost($post)
                ->setUser($user);
            $post->addUserShareReward($postUserShareReward);
            $this->entityManager->persist($post);
            $this->entityManager->flush();
        } catch (\Throwable $e) {
            $balanceHandler->rollback();
            $this->logger->error("Failed to give reward for sharing post: {$e->getMessage()}");

            throw new \Exception($this->translator->trans('api.something_went_wrong'));
        }

        /** @psalm-suppress TooManyArguments */
        $this->eventDispatcher->dispatch(
            new PostEvent($post, ActivityTypes::TOKEN_SHARED, $user),
            TokenEvents::POST_SHARED
        );

        $tokenBalance = $balanceHandler->balance(
            $user,
            $token
        );

        return $this->view([
            'message' => $this->translator->trans('api.success'),
            'balance' => $tokenBalance->getFullAvailable(),
        ], Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Get("/recent-posts-and-comments/{page}", name="recent_posts_and_comments", options={"expose"=true})
     * @Rest\RequestParam(name="all", nullable=true)
     * @Rest\RequestParam(name="max", nullable=true, requirements="\d+")
     */
    public function getRecentPostsAndComments(Request $request, int $page): view
    {
        /** @var User $user */
        $user = $this->getUser();
        $isAll = filter_var($request->query->get('all'), FILTER_VALIDATE_BOOLEAN);
        $max = (int) ($request->query->get('max') ?? 10);

        try {
            $posts = $isAll
                ? $this->postManager->getRecentPosts($page, $max)
                : $this->postManager->getRecentPostsByUserFeed($user, $page);
            $comments = $isAll
                ? $this->commentManager->getRecentComments($page, $max)
                : $this->commentManager->getRecentCommentsByUserFeed($user, $page);
        } catch (\Throwable $e) {
            $this->logger->error("Exception in getRecentPostsAndComments: {$e->getMessage()}");

            throw new ApiBadRequestException($this->translator->trans('api.something_went_wrong'));
        }

        return $this->view(
            [
                'posts' => $posts,
                'comments' => $comments,
            ],
            Response::HTTP_OK
        );
    }

    /**
     * @Rest\View()
     * @Rest\Get("/feed/{hashtag}/{page}", name="feed_by_hashtag", options={"expose"=true})
     */
    public function getFeedByHashtag(string $hashtag, int $page): view
    {
        try {
            $normalizedHashtag = $this->hashtagManager->normalizeHashtagValue($hashtag);

            $posts = $this->postManager->getPostsByHashtag($normalizedHashtag, $page);
            $comments = $this->commentManager->getCommentsByHashtag($normalizedHashtag, $page);
        } catch (\Throwable $e) {
            $this->logger->error("Exception in getFeedByHashtag: {$e->getMessage()}");

            throw new ApiBadRequestException($this->translator->trans('api.something_went_wrong'));
        }

        return $this->view(
            [
                'posts' => $posts,
                'comments' => $comments,
            ],
            Response::HTTP_OK
        );
    }

    /**
     * @Rest\View()
     * @Rest\Get("/trending-tags", name="trending_tags", options={"expose"=true})
     */
    public function getTrendingTags(): view
    {
        return $this->view(
            $this->hashtagManager->getPopularHashtags(),
            Response::HTTP_OK
        );
    }

    /**
     * @Rest\View()
     * @Rest\Get("/hashtags/search", name="search_hashtags", options={"expose"=true})
     * @Rest\QueryParam(name="query", allowBlank=false)
     */
    public function searchHashtags(ParamFetcherInterface $request): view
    {
        return $this->view(
            $this->hashtagManager->findHashtagsByKeyword((string)$request->get('query')),
            Response::HTTP_OK
        );
    }

    private function handlePostForm(
        Post $post,
        ParamFetcherInterface $request,
        string $message,
        bool $newPost = false
    ): View {
        $titleUpdated = $post->getTitle() !== $request->get('title');
        $form = $this->createForm(PostType::class, $post, ['csrf_protection' => false]);

        $form->submit($request->all());

        if (!$form->isValid()) {
            return $this->view($form, Response::HTTP_BAD_REQUEST);
        }

        if ($newPost || $titleUpdated) {
            $slug = $this->slugger->convert(
                $post->getTitle(),
                $this->postManager->getRepository()
            );

            $post->setSlug($slug);
        }

        $hashtags = $this->hashtagManager->findOrCreate($post->getContent());
        $post->setHashtags($hashtags, $newPost);

        $this->entityManager->persist($post);
        $this->entityManager->flush();

        if ($newPost) {
            $token = $post->getToken();
            $notificationType = NotificationTypes::TOKEN_NEW_POST;
            $extraData = [
                'slug' => $post->getSlug(),
            ];

            $strategy = new TokenPostNotificationStrategy(
                $this->userNotificationManager,
                $token,
                $extraData,
                $notificationType,
                $this->notificationPolicy,
                $this->mailer,
                $this->postManager
            );
            $notificationContext = new NotificationContext($strategy);
            $followers = $this->userTokenFollowManager->getFollowers($token);

            foreach ($followers as $follower) {
                if ($this->userNotificationConfigManager->isAllowedToSendNotification($follower, $token)) {
                    $notificationContext->sendNotification($follower);
                }
            }
        }

        return $this->view(["message" => $message, 'post' => $post], Response::HTTP_OK);
    }

    private function handleCommentForm(
        Comment $comment,
        ParamFetcherInterface $request,
        string $message,
        bool $isEdit,
        ?User $user = null
    ): View {
        $form = $this->createForm(CommentType::class, $comment, ['csrf_protection' => false]);

        $form->submit($request->all());

        if (!$form->isValid()) {
            return $this->view($form, Response::HTTP_BAD_REQUEST);
        }

        $hashtags = $this->hashtagManager->findOrCreate($comment->getContent());
        $comment->setHashtags($hashtags, !$isEdit);

        $this->entityManager->persist($comment);
        $this->entityManager->flush();

        if (!$isEdit) {
            $this->eventDispatcher->dispatch(
                new PostEvent($comment->getPost(), ActivityTypes::POST_COMMENTED, $user),
                TokenEvents::POST_COMMENTED
            );
        }

        return $this->view(["message" => $message, "comment" => $comment], Response::HTTP_OK);
    }
}
