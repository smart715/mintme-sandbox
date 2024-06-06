<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Activity\ActivityTypes;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Events\Activity\UserTokenEventActivity;
use App\Exception\ApiNotFoundException;
use App\Exception\UserTokenFollowException;
use App\Manager\TokenManager;
use App\Manager\UserTokenFollowManager;
use App\Manager\UserTokenManager;
use App\Utils\Symbols;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Money\Currency;
use Money\Money;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Rest\Route("/api/token")
 */
class TokenFollowController extends AbstractFOSRestController
{
    private UserTokenFollowManager $userTokenFollowManager;
    private TokenManager $tokenManager;
    private TranslatorInterface $translator;
    private UserTokenManager $userTokenManager;
    private EntityManagerInterface $em;

    public function __construct(
        UserTokenFollowManager $userTokenFollowManager,
        TokenManager $tokenManager,
        TranslatorInterface $translator,
        UserTokenManager $userTokenManager,
        EntityManagerInterface $em
    ) {
        $this->userTokenFollowManager = $userTokenFollowManager;
        $this->tokenManager = $tokenManager;
        $this->translator = $translator;
        $this->userTokenManager = $userTokenManager;
        $this->em = $em;
    }

    /**
     * @Rest\View()
     * @Rest\Patch("/follow/{tokenName}", name="token_follow", options={"expose"=true})
     */
    public function followToken(
        string $tokenName,
        EventDispatcherInterface $eventDispatcher
    ): View {
        /** @var  User $user*/
        $user = $this->getUser();
        $token = $this->getTokenByName($tokenName);

        try {
            $this->userTokenFollowManager->manualFollow($token, $user);
        } catch (UserTokenFollowException $exception) {
            if (UserTokenFollowException::USER_IS_OWNER === $exception->getCode()) {
                return $this->view(
                    ['message' => $this->translator->trans('page.pair.follow_btn.message.owner_cant_follow')],
                    Response::HTTP_BAD_REQUEST
                );
            }

            throw $exception;
        }

        $this->updateTokenUserRelation($user, $token);

        $eventDispatcher->dispatch(
            new UserTokenEventActivity($user, $token, ActivityTypes::USER_FOLLOWED),
            UserTokenEventActivity::NAME
        );

        return $this->view(
            ['message' => $this->translator->trans('page.pair.follow_btn.message.success_follow')],
            Response::HTTP_OK
        );
    }

    /**
     * @Rest\View()
     * @Rest\Patch("/unfollow/{tokenName}", name="token_unfollow", options={"expose"=true})
     */
    public function unfollowToken(
        string $tokenName
    ): View {
        /** @var User $user*/
        $user = $this->getUser();
        $token = $this->getTokenByName($tokenName);

        try {
            $this->userTokenFollowManager->manualUnfollow($token, $user);
        } catch (UserTokenFollowException $exception) {
            if (UserTokenFollowException::USER_IS_OWNER === $exception->getCode()) {
                return $this->view(
                    ['message' => $this->translator->trans('page.pair.follow_btn.message.owner_cant_follow')],
                    Response::HTTP_BAD_REQUEST
                );
            }

            throw $exception;
        }

        if ($this->isGranted('delete-from-wallet', $token)) {
            $userToken = $this->userTokenManager->findByUserToken($user, $token);

            if (!$userToken) {
                throw new ApiNotFoundException(
                    $this->translator->trans('api.tokens.user_has_not_token', ['%name%' => $tokenName])
                );
            }

            $userToken->setIsRemoved(true);
            $this->em->flush();
        }

        return $this->view(
            ['message' => $this->translator->trans('page.pair.follow_btn.message.success_unfollow')],
            Response::HTTP_OK
        );
    }

    private function getTokenByName(string $tokenName): Token
    {
        $token = $this->tokenManager->findByName($tokenName);

        if (null === $token) {
            throw $this->createNotFoundException($this->translator->trans('api.tokens.token_not_exists'));
        }

        return $token;
    }

    private function updateTokenUserRelation(User $user, Token $token): void
    {
        $userToken = $this->userTokenManager->findByUserToken($user, $token);

        if (!$userToken) {
            $this->userTokenManager->updateRelation(
                $user,
                $token,
                new Money(0, new Currency($token->getSymbol()))
            );
        } else {
            $userToken->setIsRemoved(false);
            $this->em->flush();
        }
    }
}
