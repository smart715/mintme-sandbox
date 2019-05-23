<?php declare(strict_types = 1);

namespace App\Controller;

use App\Form\ResetRequestType;
use App\Logger\UserActionLogger;
use FOS\UserBundle\Controller\ResettingController  as FOSResettingController;
use FOS\UserBundle\Form\Factory\FactoryInterface;
use FOS\UserBundle\Mailer\MailerInterface;
use FOS\UserBundle\Model\UserManagerInterface;
use FOS\UserBundle\Util\TokenGeneratorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ResettingController extends FOSResettingController
{
    /** @var UserActionLogger */
    private $userActionLogger;

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
            $this->userActionLogger->info('Forgot password');

            return parent::sendEmailAction($request);
        }

        return $this->render('@FOSUser/Resetting/request.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
