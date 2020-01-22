<?php declare(strict_types = 1);

namespace App\Controller;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Form\QuickRegistrationType;
use App\Manager\CryptoManagerInterface;
use App\Manager\UserManagerInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\Event\GetResponseUserEvent;
use FOS\UserBundle\FOSUserEvents;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * @Route("/hacker")
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
        $this->eventDispatcher = $eventDispatcher;
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
        $crypto = $cryptoManager->findBySymbol($crypto);
        $user = $this->getUser();

        if (!$crypto || !$user) {
            return $this->redirect($referer);
        }

        $amount = self::BTC_SYMBOL === $crypto->getSymbol() ?
            '0.001' : '100';

        $balanceHandler->deposit(
            $this->getUser(),
            Token::getFromCrypto($crypto),
            $moneyWrapper->parse($amount, $crypto->getSymbol())
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
     * @param Request $request
     * @param UserManagerInterface $userManager
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

            if ($userManager->findUserByEmail($email)) {
                $this->addFlash('danger', 'Email already used');
            } else {
                return $this->doQuickRegistration($request, $form, $userManager, $passwordEncoder, $email);
            }
        }

        return $this->render('pages/quick_registration.html.twig', [
            'formHeader' => 'Quick Registration',
            'form' => $form->createView(),
        ]);
    }

    /**
     * @param Request $request
     * @param FormInterface $form
     * @param UserManagerInterface $userManager
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param string $email
     * @return RedirectResponse $response
     */
    private function doQuickRegistration(
        Request $request,
        FormInterface $form,
        UserManagerInterface $userManager,
        UserPasswordEncoderInterface $passwordEncoder,
        string $email
    ): RedirectResponse {
        $user = $userManager->createUser();
        $user->setEmail($email);
        $user->setPassword(
            $passwordEncoder->encodePassword(
                $user,
                $this->quickRegistrationPassword
            )
        );
        $user->setEnabled(true);

        $event = new GetResponseUserEvent($user, $request);
        $this->eventDispatcher->dispatch(FOSUserEvents::REGISTRATION_INITIALIZE, $event);

        $event = new FormEvent($form, $request);
        $this->eventDispatcher->dispatch(FOSUserEvents::REGISTRATION_SUCCESS, $event);
        $userManager->updateUser($user);

        if (null === $event->getResponse()) {
            $url = $this->generateUrl('fos_user_registration_confirmed');
            $response = new RedirectResponse($url);
        } else {
            $response = $event->getResponse();
        }

        $this->eventDispatcher->dispatch(
            FOSUserEvents::REGISTRATION_COMPLETED,
            new FilterUserResponseEvent($user, $request, $response)
        );

        return $response;
    }
}
