<?php declare(strict_types = 1);

namespace App\Controller;

use App\Entity\Profile;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Form\QuickRegistrationType;
use App\Form\RegistrationType;
use App\Manager\CryptoManagerInterface;
use App\Manager\UserManagerInterface;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\Event\GetResponseUserEvent;
use FOS\UserBundle\FOSUserEvents;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * @Route("/hacker")
 * @Security(expression="is_granted('hacker')")
 * @codeCoverageIgnore
 */
class HackerController extends AbstractController
{
    public const BTC_SYMBOL = 'BTC';

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var string */
    private $quickRegistrationPassword;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        string $quickRegistrationPassword
    ) {
        $this->eventDispatcher           = $eventDispatcher;
        $this->quickRegistrationPassword = $quickRegistrationPassword;
    }

    /**
     * @Route("/crypto/{crypto}", name="hacker-add-crypto", options={"expose"=true})
     */
    public function addCrypto(
        string $crypto,
        Request $request,
        BalanceHandlerInterface $balanceHandler,
        CryptoManagerInterface $cryptoManager,
        MoneyWrapperInterface $moneyWrapper
    ): RedirectResponse {
        /** @var string $referer */
        $referer = $request->headers->get('referer');
        $crypto  = $cryptoManager->findBySymbol($crypto);
        $user    = $this->getUser();

        if (!$crypto || !$user) {
            return $this->redirect($referer);
        }

        $symbol = $crypto->getSymbol();

        $amount = Symbols::BTC === $symbol
            ? '0.001'
            : (Symbols::ETH === $symbol ? '0.05' :  (Symbols::USDC === $symbol ? '10' : '100'));

        /** @var User $user*/
        $user = $this->getUser();

        $balanceHandler->deposit(
            $user,
            Token::getFromCrypto($crypto),
            $moneyWrapper->parse($amount, $symbol)
        );

        return $this->redirect($referer);
    }

    /**
     * @Route(
     *     "/role/{role}", name="hacker-set-role", requirements={"role"="(user|admin)"}, options={"expose"=true}
     *     )
     */
    public function setRole(
        string $role,
        Request $request,
        UserManagerInterface $userManager
    ): RedirectResponse {
        /** @var User|null $user */
        $user = $this->getUser();

        /** @var string $referer */
        $referer = $request->headers->get('referer');

        if (!$user) {
            return $this->redirect($referer);
        }

        $user->setRoles(['user' === $role ? User::ROLE_DEFAULT : 'ROLE_SUPER_ADMIN']);

        $userManager->updateUser($user);

        $request->getSession()->invalidate();

        return $this->redirect($referer);
    }

    /**
     * @Route("/quick-registration", name="quick-registration", options={"expose"=true})
     * @param Request                      $request
     * @param UserManagerInterface         $userManager
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @return Response
     */
    public function quickRegistration(
        Request $request,
        UserManagerInterface $userManager,
        UserPasswordEncoderInterface $passwordEncoder
    ): Response {
        $form = $this->createForm(QuickRegistrationType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();
            $nickname = $form->get('nickname')->getData();

            if ($userManager->findUserByEmail($email)) {
                $this->addFlash('danger', 'Email already used');
            } else {
                return $this->doQuickRegistration(
                    $request,
                    $userManager,
                    $passwordEncoder,
                    $email,
                    $nickname
                );
            }
        }

        return $this->render('pages/quick_registration.html.twig', [
            'formHeader' => 'Quick Registration',
            'form'       => $form->createView(),
        ]);
    }

    /**
     * @param Request                      $request
     * @param UserManagerInterface         $userManager
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param string                       $email
     * @return RedirectResponse $response
     */
    private function doQuickRegistration(
        Request $request,
        UserManagerInterface $userManager,
        UserPasswordEncoderInterface $passwordEncoder,
        string $email,
        string $nickname
    ): RedirectResponse {
        /** @var User $user */
        $user = $userManager->createUser();
        $user->setEmail($email);
        $user->setPassword(
            $passwordEncoder->encodePassword(
                $user,
                $this->quickRegistrationPassword
            )
        );

        $profile = new Profile($user);
        $profile->setNickname($nickname);
        $profile->setNextReminderDate(new \DateTime('+1 month'));
        $user->setProfile($profile);

        $em = $this->getDoctrine()->getManager();
        $em->persist($user);
        $em->flush();

        $url = $this->generateUrl('fos_user_registration_confirmed');
        $response = new RedirectResponse($url);

        $event = new GetResponseUserEvent($user, $request);
        /** @psalm-suppress TooManyArguments */
        $this->eventDispatcher->dispatch($event, FOSUserEvents::REGISTRATION_INITIALIZE);

        $event = new FormEvent($this->createForm(RegistrationType::class, $user), $request);
        /** @psalm-suppress TooManyArguments */
        $this->eventDispatcher->dispatch($event, FOSUserEvents::REGISTRATION_SUCCESS);

        /** @psalm-suppress TooManyArguments */
        $this->eventDispatcher->dispatch(
            new FilterUserResponseEvent($user, $request, $response),
            FOSUserEvents::REGISTRATION_COMPLETED
        );

        $user->setEnabled(true);
        $user->setConfirmationToken(null);

        $event = new GetResponseUserEvent($user, $request);
        /** @psalm-suppress TooManyArguments */
        $this->eventDispatcher->dispatch($event, FOSUserEvents::REGISTRATION_CONFIRM);
        $userManager->updateUser($user);

        /** @psalm-suppress TooManyArguments */
        $this->eventDispatcher->dispatch(
            new FilterUserResponseEvent($user, $request, $response),
            FOSUserEvents::REGISTRATION_CONFIRMED
        );

        return $response;
    }

    /**
     * @Route(
     *     "/hacker-toggle-info-bar",
     *     name="hacker-toggle-info-bar",
     *     options={"expose"=true}
     *     )
     * @param Request $request
     * @return RedirectResponse
     */
    public function toggleInfoBar(Request $request): RedirectResponse
    {
        /** @var string $referer */
        $referer = $request->headers->get('referer');
        $session = $request->getSession();
        $session->set('show_info_bar', !$session->get('show_info_bar', true));

        return $this->redirect($referer);
    }
}
