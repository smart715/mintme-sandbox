<?php declare(strict_types = 1);

namespace App\Controller;

use App\Activity\ActivityTypes;
use App\Config\WithdrawalDelaysConfig;
use App\Controller\Traits\ReferralRedirectionTrait;
use App\Entity\Bonus;
use App\Entity\Token\Token;
use App\Entity\TokenSignupBonusCode;
use App\Entity\User;
use App\Events\Activity\UserEventActivity;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Balance\BalanceTransactionBonusType;
use App\Logger\UserActionLogger;
use App\Mailer\MailerInterface;
use App\Manager\AirdropCampaignManagerInterface;
use App\Manager\AirdropReferralCodeManagerInterface;
use App\Manager\BonusManagerInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Manager\TokenSignupBonusCodeManagerInterface;
use App\Manager\UserManagerInterface;
use App\Manager\UserNotificationConfigManagerInterface;
use App\Repository\TokenSignupBonusCodeRepository;
use App\Security\Request\RefererRequestHandlerInterface;
use App\Utils\LockFactory;
use App\Utils\Symbols;
use App\Validator\Constraints\TokenSignupBonusCode as TokenSignupBonusCodeConstraint;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use FOS\UserBundle\Controller\RegistrationController as FOSRegistrationController;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\Event\GetResponseUserEvent;
use FOS\UserBundle\Form\Factory\FactoryInterface;
use FOS\UserBundle\FOSUserEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

/** @phpstan-ignore-next-line final class */
class RegistrationController extends FOSRegistrationController
{
    private UserActionLogger $userActionLogger;
    private EventDispatcherInterface $eventDispatcher;
    private FactoryInterface $formFactory;
    private UserManagerInterface $userManager;
    private BonusManagerInterface $bonusManager;
    private BalanceHandlerInterface $balanceHandler;
    private MoneyWrapperInterface $moneyWrapper;
    private CryptoManagerInterface $cryptoManager;
    private EntityManagerInterface $em;
    private UserNotificationConfigManagerInterface $userNotificationConfigManager;
    private MailerInterface $mailer;
    private string $mintmeHostFreeDays;
    private string $mintmeHostPrice;
    private string $mintmeHostPath;
    private RefererRequestHandlerInterface $refererRequestHandler;
    private AirdropCampaignManagerInterface $airdropCampaignManager;
    private ValidatorInterface $validator;
    private TokenSignupBonusCodeRepository $tokenSignUpBonusRepository;
    private TranslatorInterface $translations;
    private TokenManagerInterface $tokenManager;
    private TokenSignupBonusCodeManagerInterface $tokenSignUpBonusCodeManager;
    private LockFactory $lockFactory;
    private WithdrawalDelaysConfig $withdrawalDelaysConfig;

    use ReferralRedirectionTrait;

    public function __construct(
        UserActionLogger $userActionLogger,
        EventDispatcherInterface $eventDispatcher,
        FactoryInterface $formFactory,
        UserManagerInterface $userManager,
        TokenStorageInterface $tokenStorage,
        BonusManagerInterface $bonusManager,
        BalanceHandlerInterface $balanceHandler,
        MoneyWrapperInterface $moneyWrapper,
        CryptoManagerInterface $cryptoManager,
        EntityManagerInterface $entityManager,
        UserNotificationConfigManagerInterface $userNotificationConfigManager,
        MailerInterface $mailer,
        string $mintmeHostFreeDays,
        string $mintmeHostPrice,
        string $mintmeHostPath,
        RefererRequestHandlerInterface $refererRequestHandler,
        AirdropCampaignManagerInterface $airdropCampaignManager,
        ValidatorInterface $validator,
        TokenSignupBonusCodeRepository $tokenSignUpBonusRepository,
        TranslatorInterface $translations,
        TokenManagerInterface $tokenManager,
        TokenSignupBonusCodeManagerInterface $tokenSignUpBonusCodeManager,
        LockFactory $lockFactory,
        AirdropReferralCodeManagerInterface $arcManager,
        WithdrawalDelaysConfig $withdrawalDelaysConfig
    ) {
        $this->userActionLogger = $userActionLogger;
        $this->eventDispatcher = $eventDispatcher;
        $this->formFactory = $formFactory;
        $this->userManager = $userManager;
        $this->bonusManager = $bonusManager;
        $this->balanceHandler = $balanceHandler;
        $this->moneyWrapper = $moneyWrapper;
        $this->cryptoManager = $cryptoManager;
        $this->em = $entityManager;
        $this->userNotificationConfigManager = $userNotificationConfigManager;
        $this->mailer = $mailer;
        $this->mintmeHostFreeDays = $mintmeHostFreeDays;
        $this->mintmeHostPrice =$mintmeHostPrice;
        $this->mintmeHostPath = $mintmeHostPath;
        $this->airdropCampaignManager = $airdropCampaignManager;
        $this->tokenSignUpBonusRepository = $tokenSignUpBonusRepository;
        $this->validator = $validator;
        $this->translations = $translations;
        $this->tokenManager = $tokenManager;
        $this->tokenSignUpBonusCodeManager = $tokenSignUpBonusCodeManager;
        parent::__construct($eventDispatcher, $formFactory, $userManager, $tokenStorage);
        $this->refererRequestHandler = $refererRequestHandler;
        $this->lockFactory = $lockFactory;
        $this->arcManager = $arcManager;
        $this->withdrawalDelaysConfig = $withdrawalDelaysConfig;
    }

    /**
     * @Route("/sign-up", name="sign_up")
     * @param Request $request
     * @return Response
     */
    public function signUpLanding(Request $request): Response
    {
        $form = $this->formFactory->createForm();

        if ($this->bonusManager->isLimitReached(
            (string)$this->getParameter('landing_web_bonus_limit'),
            Bonus::SIGN_UP_TYPE
        )) {
            return $this->redirectToRoute('homepage');
        }

        $response = $this->checkForm($form, $request);

        if ($response) {
            return $response;
        }

        return $this->render('pages/sign_up_landing.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/register", name="register", options = {"expose": true})
     */
    public function registerAction(Request $request): Response
    {
        if (!$request->query->get('withReferral')) {
            $request->cookies->remove('login-bonus');
            $request->cookies->remove('referral-code');
            $request->cookies->remove('referral-type');
            $request->cookies->remove('referral-token');
        }

        $form = $this->formFactory->createForm();

        try {
            $response = $this->checkForm($form, $request);
        } catch (\Throwable $exception) {
            $this->addFlash('danger', 'an error occurred please try again!');

            return $this->render('@FOSUser/Registration/register.html.twig', [
                'form' => $form->createView(),
            ]);
        }

        $refers = $request->headers->get('Referer');

        if ($refers && !in_array($refers, $this->refererRequestHandler->refererUrlsToSkip(), true)) {
            $this->get('session')->set('register_referer', $refers);
        }

        if ($response) {
            return $response;
        }

        $this->checkTokenSignupBonusCode($request);

        if ($request->get('formContentOnly', false)) {
            return $this->render("@FOSUser/Registration/register_content_body.html.twig", [
                'form' => $form->createView(),
                'embedded' => true,
            ]);
        }

        return $this->render('@FOSUser/Registration/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @param FormInterface $form
     * @param Request $request
     * @return RedirectResponse|Response|null
     */
    private function checkForm(FormInterface $form, Request $request)
    {
        /** @var User $user */
        $user = $this->userManager->createUser();
        $user->setEnabled(true);
        $event = new GetResponseUserEvent($user, $request);
        /** @psalm-suppress TooManyArguments */
        $this->eventDispatcher->dispatch($event, FOSUserEvents::REGISTRATION_INITIALIZE);

        if (null !== $event->getResponse()) {
            return $event->getResponse();
        }

        $form->setData($user);

        $form->handleRequest($request);
        $user->getProfile()->setNextReminderDate(new \DateTime('+1 month'));

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $event = new FormEvent($form, $request);

                /** @psalm-suppress TooManyArguments */
                $this->eventDispatcher->dispatch($event, FOSUserEvents::REGISTRATION_SUCCESS);
                $this->eventDispatcher->dispatch(
                    new UserEventActivity($user, ActivityTypes::USER_REGISTERED),
                    UserEventActivity::NAME
                );

                $this->userManager->updateUser($user);

                if ($this->generateUrl('sign_up', [], UrlGeneratorInterface::ABSOLUTE_URL)
                    === $request->headers->get('referer')) {
                    $bonus = new Bonus(
                        $user,
                        Bonus::PENDING_STATUS,
                        $this->moneyWrapper->parse(
                            (string)$this->getParameter('landing_web_bonus'),
                            Symbols::WEB
                        )->getAmount(),
                        Bonus::SIGN_UP_TYPE,
                        Symbols::WEB
                    );
                    $user->setBonus($bonus);
                    $this->em->persist($bonus);
                    $this->em->flush();
                }

                if ($request->get('formContentOnly', false)) {
                    $this->get('session')->set('registered_form_content_only', true);
                }

                if (null === $event->getResponse()) {
                    $url = $this->generateUrl('fos_user_registration_confirmed');
                    $response = new RedirectResponse($url);
                } else {
                    $response = $event->getResponse();
                }

                /** @psalm-suppress TooManyArguments */
                $this->eventDispatcher->dispatch(
                    new FilterUserResponseEvent($user, $request, $response),
                    FOSUserEvents::REGISTRATION_COMPLETED
                );

                $this->processTokenSignupBonusCode($request, $user);

                return $response;
            }

            $event = new FormEvent($form, $request);
            /** @psalm-suppress TooManyArguments */
            $this->eventDispatcher->dispatch($event, FOSUserEvents::REGISTRATION_FAILURE);

            if (null !== $response = $event->getResponse()) {
                return $response;
            }
        }

        return null;
    }

    public function confirmedAction(Request $request): Response
    {
        /** @var User|null */
        $user = $this->getUser();

        if (!$user instanceof User) {
            return parent::confirmedAction($request);
        }

        $withdrawAfterRegisterSeconds = $this->withdrawalDelaysConfig->getWithdrawAfterRegisterTime();

        $lockWithdrawRegister = $this->lockFactory->createLock(
            LockFactory::LOCK_WITHDRAW_AFTER_REGISTER.$user->getId(),
            $withdrawAfterRegisterSeconds,
            false
        );

        $lockWithdrawRegister->acquire();

        $bonus = $user->getBonus();
        $this->userNotificationConfigManager->initializeUserNotificationConfig($user);
        $this->mailer->sendMintmeHostMail(
            $user,
            $this->mintmeHostPrice,
            $this->mintmeHostFreeDays,
            $this->mintmeHostPath
        );

        $this->airdropCampaignManager->claimAirdropsActionsFromSessionData($user);

        if ($bonus &&
            Bonus::PENDING_STATUS === $user->getBonus()->getStatus() &&
            Bonus::SIGN_UP_TYPE === $user->getBonus()->getType()) {
            $crypto = $this->cryptoManager->findBySymbol($bonus->getTradableName());

            if (!$crypto) {
                return parent::confirmedAction($request);
            }

            $bonus->setStatus(Bonus::PAID_STATUS);
            $this->em->persist($bonus);
            $this->em->flush();

            try {
                $this->balanceHandler->beginTransaction();
                $this->balanceHandler->depositBonus(
                    $user,
                    $crypto,
                    $this->moneyWrapper->parse(
                        (string)$this->getParameter('landing_web_bonus'),
                        $crypto->getSymbol()
                    ),
                    BalanceTransactionBonusType::SIGN_UP
                );
            } catch (Throwable $exception) {
                $this->balanceHandler->rollback();
                $this->em->rollback();
            }
        }

        $this->withdrawTokenSignupBonusCode($user, $bonus);

        /** @var Session $session */
        $session = $this->get('session');
        $referer = $session->get('register_referer');

        if ($referer && $this->refererRequestHandler->isRefererValid($referer)) {
            $session->remove('register_referer');

            $path = $this->refererRequestHandler->getRefererPathData();

            if ('token_show' === $path['_route'] && 'buy' === $path['tab'] && 'signup' === $path['modal']) {
                return $this->redirectToRoute('wallet');
            }

            return $this->redirect($referer);
        }

        if ($referralRedirection = $this->referralRedirect($request, $this->tokenManager)) {
            return $referralRedirection;
        }

        return parent::confirmedAction($request);
    }

    private function checkTokenSignupBonusCode(Request $request): void
    {
        $tokenSignUpBonus = $this->getSignUpBonusCode($request);

        if (!$tokenSignUpBonus) {
            return;
        }

        $token = $tokenSignUpBonus->getToken();
        $this->addFlash('success', $this->translations->trans(
            'api.tokens.token_sign_up_bonus.valid_code',
            [
                'amount' => $this->moneyWrapper->format($tokenSignUpBonus->getAmount(), false),
                'tokenName' => $token->getName(),
            ]
        ));
    }

    private function processTokenSignupBonusCode(Request $request, User $user): void
    {
        $tokenSignUpBonus = $this->getSignUpBonusCode($request);

        if (!$tokenSignUpBonus) {
            return;
        }

        $bonus = new Bonus(
            $user,
            Bonus::PENDING_STATUS,
            $tokenSignUpBonus->getAmount()->getAmount(),
            Bonus::TOKEN_SIGN_UP_TYPE,
            $tokenSignUpBonus->getToken()->getName()
        );

        $user->setBonus($bonus);
        $this->em->persist($bonus);
        $this->em->flush();
    }

    private function getSignUpBonusCode(Request $request): ?TokenSignupBonusCode
    {
        $bonusCode = $request->cookies->get('login-bonus');

        if (!$bonusCode) {
            return null;
        }

        $bonusCodeErrors = $this->validator->validate($bonusCode, new TokenSignupBonusCodeConstraint());

        if (count($bonusCodeErrors) > 0) {
            foreach ($bonusCodeErrors as $error) {
                $this->addFlash('danger', $error->getMessage());
            }

            return null;
        }

        return $this->tokenSignUpBonusRepository->findByCode($bonusCode);
    }

    private function withdrawTokenSignupBonusCode(User $user, ?Bonus $bonus): void
    {
        if (!$bonus
            || Bonus::PENDING_STATUS !== $bonus->getStatus()
            || Bonus::TOKEN_SIGN_UP_TYPE !== $bonus->getType()
        ) {
            return;
        }

        $token = $this->tokenManager->findByName($bonus->getTradableName());

        if (!$token) {
            return;
        }

        $this->tokenSignUpBonusCodeManager->withdrawTokenSignupBonus(
            $token,
            $user,
            $bonus->getQuantity()
        );

        $bonus->setStatus(Bonus::PENDING_CLAIM_STATUS);
        $this->em->persist($bonus);
        $this->em->flush();

        $this->userActionLogger->info('user '.$user->getId().' withdraw token sign up bonus', [
            'token' => $token->getId(),
            'quantity' => $bonus->getQuantity(),
        ]);

        $this->addFlash(
            'success',
            $this->translations->trans(
                'api.tokens.token_sign_up_bonus.success',
                [
                    'amount' =>  $this->moneyWrapper->format($bonus->getQuantity(), false),
                    'tokenName' => $token->getName(),
                ]
            )
        );
    }
}
