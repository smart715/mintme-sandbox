<?php declare(strict_types = 1);

namespace App\Controller;

use App\Entity\Bonus;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Mailer\MailerInterface;
use App\Manager\AirdropCampaignManagerInterface;
use App\Manager\AirdropReferralCodeManagerInterface;
use App\Manager\BonusManagerInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\UserManagerInterface;
use App\Manager\UserNotificationConfigManagerInterface;
use App\Security\Request\RefererRequestHandlerInterface;
use App\TwigExtension\IsEmbededExtension;
use App\Utils\Symbols;
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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Throwable;

class RegistrationController extends FOSRegistrationController
{
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
    private AirdropReferralCodeManagerInterface $arcManager;
    private RefererRequestHandlerInterface $refererRequestHandler;
    private AirdropCampaignManagerInterface $airdropCampaignManager;
    private SessionInterface $session;

    public function __construct(
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
        AirdropReferralCodeManagerInterface $arcManager,
        RefererRequestHandlerInterface $refererRequestHandler,
        AirdropCampaignManagerInterface $airdropCampaignManager,
        SessionInterface $session
    ) {
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
        $this->arcManager = $arcManager;
        $this->airdropCampaignManager = $airdropCampaignManager;
        $this->session = $session;
        parent::__construct($eventDispatcher, $formFactory, $userManager, $tokenStorage);
        $this->refererRequestHandler = $refererRequestHandler;
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
            $this->getParameter('landing_web_bonus_limit'),
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

        if ($request->get('formContentOnly', false)) {
            return $this->render("@FOSUser/Registration/register_content_body.html.twig", [
                'form' => $form->createView(),
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

                $this->userManager->updateUser($user);

                if ($this->generateUrl('sign_up', [], UrlGeneratorInterface::ABSOLUTE_URL)
                    === $request->headers->get('referer')) {
                    $bonus = new Bonus(
                        $user,
                        Bonus::PENDING_STATUS,
                        $this->getParameter('landing_web_bonus'),
                        Bonus::SIGN_UP_TYPE
                    );
                    $user->setBonus($bonus);
                    $this->em->persist($bonus);
                    $this->em->flush();
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

    public function checkEmailAction(Request $request): Response
    {
        if (!$request->get('page') && preg_match(
            IsEmbededExtension::EMBEDED_REGEX,
            $request->headers->get('referer') ?? ''
        )) {
            return $this->redirectToRoute('fos_user_registration_check_email', [
                'page' => 'embeded',
            ]);
        }

        $email = $request->getSession()->get('fos_user_send_confirmation_email/email');

        if (empty($email)) {
            return new RedirectResponse($this->generateUrl('fos_user_registration_register'));
        }

        $request->getSession()->remove('fos_user_send_confirmation_email/email');
        $user = $this->userManager->findUserByEmail($email);

        if (null === $user) {
            return new RedirectResponse($this->container->get('router')->generate('fos_user_security_login'));
        }

        return $this->render('@FOSUser/Registration/check_email.html.twig', array(
            'user' => $user,
        ));
    }

    public function confirmedAction(Request $request): Response
    {
        /** @var User|null */
        $user = $this->getUser();

        if (!$user instanceof User) {
            return parent::confirmedAction($request);
        }

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
            $crypto = $this->cryptoManager->findBySymbol(Symbols::WEB);

            if (!$crypto) {
                return parent::confirmedAction($request);
            }

            $bonus->setStatus(Bonus::PAID_STATUS);
            $this->em->persist($bonus);
            $this->em->flush();

            try {
                $this->balanceHandler->deposit(
                    $user,
                    Token::getFromCrypto($crypto),
                    $this->moneyWrapper->parse(
                        (string)$this->getParameter('landing_web_bonus'),
                        $crypto->getSymbol()
                    )
                );
            } catch (Throwable $exception) {
                $this->em->rollback();
            }
        }

        /** @var Session $session */
        $session = $this->get('session');
        $referer = $session->get('register_referer');

        if ($referer && $this->refererRequestHandler->isRefererValid($referer)) {
            $session->remove('register_referer');

            $path = $this->refererRequestHandler->getRefererPathData();

            if ('token_show' === $path['_route'] && 'buy' === $path['tab'] && 'signup' === $path['modal']) {
                return $this->redirectToRoute('wallet');
            }

            if (preg_match(IsEmbededExtension::EMBEDED_REGEX, $referer)) {
                $referer = SecurityController::MAIN_REDIRECT_ROUTE;
                $this->session->set('login_referer', $referer);
            }

            return $this->redirect($referer);
        }

        $referralCode = $request->cookies->get('referral-code');
        $referralType = $request->cookies->get('referral-type');

        if (null !== $referralCode) {
            switch ($referralType) {
                case 'invite':
                    $referrerUser = $this->userManager->findByReferralCode($referralCode);
                    $token = $referrerUser
                        ? $referrerUser->getProfile()->getFirstToken()
                        : null;

                    break;
                case 'airdrop':
                    $arc = $this->arcManager->decode($referralCode);
                    $token = $arc
                        ? $arc->getAirdrop()->getToken()
                        : null;

                    break;
            }
        }

        if (isset($token)) {
            return $this->redirectToRoute("token_show", ["name" => $token->getName()]);
        }

        return parent::confirmedAction($request);
    }
}
