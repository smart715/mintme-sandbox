<?php declare(strict_types = 1);

namespace App\Controller;

use App\Entity\Bonus;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Manager\CryptoManagerInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use FOS\UserBundle\Controller\RegistrationController as FOSRegistrationController;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\Event\GetResponseUserEvent;
use FOS\UserBundle\Form\Factory\FactoryInterface;
use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Throwable;

class RegistrationController extends FOSRegistrationController
{
    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var FactoryInterface */
    private $formFactory;

    /** @var UserManagerInterface */
    private $userManager;

    /** @var EntityManagerInterface */
    private $repo;

    /** @var BalanceHandlerInterface */
    private $balanceHandler;

    /** @var MoneyWrapperInterface */
    private $moneyWrapper;

    /** @var CryptoManagerInterface */
    private $cryptoManager;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        FactoryInterface $formFactory,
        UserManagerInterface $userManager,
        TokenStorageInterface $tokenStorage,
        EntityManagerInterface $entityManager,
        BalanceHandlerInterface $balanceHandler,
        MoneyWrapperInterface $moneyWrapper,
        CryptoManagerInterface $cryptoManager
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->formFactory = $formFactory;
        $this->userManager = $userManager;
        $this->repo = $entityManager;
        $this->balanceHandler = $balanceHandler;
        $this->moneyWrapper = $moneyWrapper;
        $this->cryptoManager = $cryptoManager;
        parent::__construct($eventDispatcher, $formFactory, $userManager, $tokenStorage);
    }

    /**
     * @Route("/sign-up", name="sign_up")
     * @param Request $request
     * @return Response
     */
    public function signUpLanding(Request $request): Response
    {
        $form = $this->formFactory->createForm()->add('bonus', HiddenType::class);

        $response = $this->checkForm($form, $request);

        if ($response) {
            return $response;
        }

        return $this->render('pages/sign_up_landing.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    public function registerAction(Request $request): Response
    {
        $form = $this->formFactory->createForm();

        $response = $this->checkForm($form, $request);

        if ($response) {
            return $response;
        }

        return $this->render('@FOSUser/Registration/register.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * @param FormInterface $form
     * @param Request $request
     * @return RedirectResponse|Response|null
     */
    private function checkForm(FormInterface $form, Request $request)
    {
        $user = $this->userManager->createUser();
        $user->setEnabled(true);

        $event = new GetResponseUserEvent($user, $request);
        $this->eventDispatcher->dispatch(FOSUserEvents::REGISTRATION_INITIALIZE, $event);

        if (null !== $event->getResponse()) {
            return $event->getResponse();
        }

        $form->setData($user);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $event = new FormEvent($form, $request);
                $this->eventDispatcher->dispatch(FOSUserEvents::REGISTRATION_SUCCESS, $event);

                $this->userManager->updateUser($user);

                if ($form->has('bonus')) {
                    $bonus = new Bonus($user, Bonus::PENDING_STATUS, Bonus::BONUS_WEB);
                    $this->repo->persist($bonus);
                    $this->repo->flush();
                    $user->setBonus($bonus);
                }

                if (null === $response = $event->getResponse()) {
                    $url = $this->generateUrl('fos_user_registration_confirmed');
                    $response = new RedirectResponse($url);
                }

                $this->eventDispatcher->dispatch(
                    FOSUserEvents::REGISTRATION_COMPLETED,
                    new FilterUserResponseEvent($user, $request, $response)
                );

                return $response;
            }

            $event = new FormEvent($form, $request);
            $this->eventDispatcher->dispatch(FOSUserEvents::REGISTRATION_FAILURE, $event);

            if (null !== $response = $event->getResponse()) {
                return $response;
            }
        }
    }

    public function confirmedAction(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->getBonus() && Bonus::PENDING_STATUS === $user->getBonus()->getStatus()) {
            $crypto = $this->cryptoManager->findBySymbol(Token::WEB_SYMBOL);

            if (!$crypto) {
                return parent::confirmedAction($request);
            }

            $bonus = $user->getBonus();
            $bonus->setStatus(Bonus::PAID_STATUS);
            $this->repo->persist($bonus);
            $this->repo->flush();

            try {
                $this->balanceHandler->deposit(
                    $user,
                    Token::getFromCrypto($crypto),
                    $this->moneyWrapper->parse(
                        (string)Bonus::BONUS_WEB,
                        $crypto->getSymbol()
                    )
                );
            } catch (Throwable $exception) {
                $this->repo->rollback();
                $this->addFlash('danger', 'Exchanger connection lost. Try again.');
            }
        }

        return parent::confirmedAction($request);
    }
}
