<?php declare(strict_types = 1);

namespace App\Controller;

use App\Config\WithdrawalDelaysConfig;
use App\Entity\User;
use App\Events\PasswordChangeEvent;
use App\Events\UserChangeEvents;
use App\Form\ResetRequestType;
use App\Form\ResettingType;
use App\Logger\UserActionLogger;
use App\Services\TranslatorService\TranslatorInterface;
use FOS\UserBundle\Controller\ResettingController as FOSResettingController;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\Event\GetResponseUserEvent;
use FOS\UserBundle\Form\Factory\FactoryInterface;
use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Mailer\MailerInterface;
use FOS\UserBundle\Model\UserManagerInterface;
use FOS\UserBundle\Util\TokenGeneratorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * @codeCoverageIgnore
 * @phpstan-ignore-next-line final class
 */
class ResettingController extends FOSResettingController
{
    private UserActionLogger $userActionLogger;
    private UserManagerInterface $userManager;
    private EventDispatcherInterface $eventDispatcher;
    private UserPasswordEncoderInterface $encoder;
    private TranslatorInterface $translator;
    private WithdrawalDelaysConfig $withdrawalDelaysConfig;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        FactoryInterface $formFactory,
        UserManagerInterface $userManager,
        TokenGeneratorInterface $tokenGenerator,
        MailerInterface $mailer,
        int $retryTtl,
        UserActionLogger $userActionLogger,
        TranslatorInterface $translator,
        UserPasswordEncoderInterface $encoder,
        WithdrawalDelaysConfig $withdrawalDelaysConfig
    ) {
        $this->userActionLogger = $userActionLogger;
        $this->userManager = $userManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->translator = $translator;
        $this->encoder = $encoder;
        $this->withdrawalDelaysConfig = $withdrawalDelaysConfig;

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
        /** @var User|null $user */
        $user = $this->getUser();

        if ($user) {
            return $this->redirectToRoute('trading');
        }

        $form = $this->createForm(ResetRequestType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User|null $user */
            $user = $this->userManager->findUserByEmail($request->get('username'));

            if ($user && $user->isBlocked()) {
                return $this->render('pages/account_blocked.html.twig', [
                    'email' => $user->getEmail(),
                ]);
            }

            if ($user && !$user->isEnabled()) {
                $this->addFlash(
                    'error',
                    $this->translator->trans('form.reset.email_not_confirmed')
                );

                return $this->redirectToRoute('fos_user_resetting_request');
            }

            $this->userActionLogger->info('Forgot password', ['username' => $form->get('username')->getData()]);

            parent::sendEmailAction($request);

            return $this->render('@FOSUser/Resetting/check_email.html.twig', [
                'tokenLifetime' => ceil($this->getParameter('password_reset_retry_time') / 3600),
            ]);
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

        $event = new GetResponseUserEvent($user, $request);
        /** @psalm-suppress TooManyArguments */
        $this->eventDispatcher->dispatch($event, FOSUserEvents::RESETTING_RESET_INITIALIZE);

        $resettingForm = $this->createForm(ResettingType::class, $user);
        $resettingForm->handleRequest($request);

        $eventResponse = $event->getResponse();

        if (null !== $eventResponse) {
            return $eventResponse;
        }

        $isEnabled = $user->isEnabled();

        if ($resettingForm->isSubmitted() && $resettingForm->isValid()) {
            if ($this->encoder->isPasswordValid($user, $resettingForm['plainPassword']->getData())) {
                $this->addFlash(
                    'error',
                    $this->translator->trans('passwordmeter.duplicate')
                );
            } else {
                $event = new FormEvent($resettingForm, $request);
                /** @psalm-suppress TooManyArguments */
                $this->eventDispatcher->dispatch($event, FOSUserEvents::RESETTING_RESET_SUCCESS);

                /** @var User $user */
                $this->eventDispatcher->dispatch(
                    new PasswordChangeEvent($this->withdrawalDelaysConfig, $user),
                    UserChangeEvents::PASSWORD_UPDATED
                );

                $user->setEnabled($isEnabled);
                $this->userManager->updatePassword($user);
                $this->userManager->updateUser($user);

                $response = $this->redirectToRoute('fos_user_security_login');

                /** @psalm-suppress TooManyArguments */
                $this->eventDispatcher->dispatch(
                    new FilterUserResponseEvent($user, $request, $response),
                    FOSUserEvents::RESETTING_RESET_COMPLETED
                );

                return $response;
            }
        }

        return $this->render('bundles/FOSUserBundle/Resetting/reset.html.twig', [
            'token' => $token,
            'resettingForm' => $resettingForm->createView(),
        ]);
    }
}
