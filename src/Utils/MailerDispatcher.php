<?php

namespace App\Utils;

use FOS\UserBundle\Mailer\MailerInterface;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Model\UserManagerInterface as BaseUserManager;
use FOS\UserBundle\Util\TokenGeneratorInterface;

class MailerDispatcher implements MailerDispatcherInterface
{
    /** @var MailerInterface */
    private $mailer;

    /** @var TokenGeneratorInterface */
    private $tokenGenerator;

    /** @var BaseUserManager */
    private $userManager;


    public function __construct(
        BaseUserManager $userManager,
        TokenGeneratorInterface $tokenGenerator,
        MailerInterface $mailer
    ) {
        $this->userManager = $userManager;
        $this->tokenGenerator = $tokenGenerator;
        $this->mailer = $mailer;
    }

    public function sendEmailConfirmation(UserInterface $user): void
    {
        $user->setConfirmationToken($this->tokenGenerator->generateToken());
        $this->mailer->sendConfirmationEmailMessage($user);
    }

    public function sendResettingEmail(UserInterface $user): void
    {
        $user->setConfirmationToken($this->tokenGenerator->generateToken());
        $user->setPasswordRequestedAt(new \DateTime());
        $this->userManager->updateUser($user);
        $this->mailer->sendResettingEmailMessage($user);
    }
}
