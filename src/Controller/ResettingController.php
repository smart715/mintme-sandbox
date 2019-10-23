<?php declare(strict_types = 1);

namespace App\Controller;

use App\Form\ResetRequestType;
use App\Form\ResettingType;
use App\Logger\UserActionLogger;
use FOS\UserBundle\Controller\ResettingController as FOSResettingController;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use FOS\UserBundle\Form\Factory\FactoryInterface;
use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Mailer\MailerInterface;
use FOS\UserBundle\Model\UserManagerInterface;
use FOS\UserBundle\Util\TokenGeneratorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/** @codeCoverageIgnore  */
class ResettingController extends FOSResettingController
{
    /** @var UserActionLogger */
    private $userActionLogger;

    /** @var UserManagerInterface */
    private $userManager;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        FactoryInterface $formFactory,
        UserManagerInterface $userManager,
        TokenGeneratorInterface $tokenGenerator,
        MailerInterface $mailer,
        int $retryTtl,
        UserActionLogger $userActionLogger
    ) {
        $this->userActionLogger = $userActionLogger;
        $this->userManager = $userManager;
        $this->eventDispatcher = $eventDispatcher;
        parent::__construct(
            $eventDispatcher,
            $formFactory,
            $userManager,
            $tokenGenerator,
            $mailer,
            $retryTtl
        );
    }

    public function sendEmailAction(Request $request): Response
    {
        $form = $this->createForm(ResetRequestType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->userActionLogger->info('Forgot password', ['username' => $form->get('username')->getData()]);

            return parent::sendEmailAction($request);
        }

        return $this->render('@FOSUser/Resetting/request.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /** {@inheritdoc} */
    public function resetAction(Request $request, $token): Response
    {
        $user = $this->userManager->findUserByConfirmationToken($token);

        if (null === $user) {
            return $this->render('pages/404.html.twig');
        }

        $resettingForm = $this->createForm(ResettingType::class, $user);
        $resettingForm->handleRequest($request);

        if ($resettingForm->isSubmitted() && $resettingForm->isValid()) {
            $this->userManager->updatePassword($user);
            $this->userManager->updateUser($user);
            $this->eventDispatcher->dispatch(
                FOSUserEvents::RESETTING_RESET_COMPLETED,
                new FilterUserResponseEvent(
                    $user,
                    $request,
                    $this->render('bundles/FOSUserBundle/Resetting/reset.html.twig', [
                    'token' => $token,
                    'resettingForm' => $resettingForm->createView(),
                    ])
                )
            );

            return $this->redirectToRoute('fos_user_security_login', [], 301);
        }

        return $this->render('bundles/FOSUserBundle/Resetting/reset.html.twig', [
            'token' => $token,
            'resettingForm' => $resettingForm->createView(),
        ]);
    }
}
