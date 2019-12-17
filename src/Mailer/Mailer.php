<?php declare(strict_types = 1);

namespace App\Mailer;

use App\Entity\PendingWithdrawInterface;
use App\Entity\TradebleInterface;
use App\Entity\User;
use Scheb\TwoFactorBundle\Mailer\AuthCodeMailerInterface;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;
use Swift_Mailer;
use Swift_Message;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Templating\EngineInterface;

/** @codeCoverageIgnore */
class Mailer implements MailerInterface, AuthCodeMailerInterface
{
    /** @var string */
    protected $mail;

    /** @var Swift_Mailer */
    protected $mailer;

    /** @var EngineInterface */
    protected $twigEngine;

    /** @var UrlGeneratorInterface */
    protected $urlGenerator;

    public function __construct(
        string $mail,
        Swift_Mailer $mailer,
        EngineInterface $twigEngine,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->mail = $mail;
        $this->mailer = $mailer;
        $this->twigEngine = $twigEngine;
        $this->urlGenerator = $urlGenerator;
    }

    public function sendWithdrawConfirmationMail(User $user, PendingWithdrawInterface $withdrawData): void
    {
        $confirmLink = $this->urlGenerator->generate(
            'withdraw-confirm',
            ['hash' => $withdrawData->getHash()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $body = $this->twigEngine->render('mail/withdraw_accept.html.twig', [
            'user' => $user,
            'confirmationUrl' => $confirmLink,
        ]);

        $textBody = $this->twigEngine->render('mail/withdraw_accept.txt.twig', [
            'user' => $user,
            'confirmationUrl' => $confirmLink,
        ]);

        $msg = (new Swift_Message('Confirm withdraw'))
            ->setFrom([$this->mail => 'Mintme'])
            ->setTo($user->getEmail())
            ->setBody($body, 'text/html')
            ->addPart($textBody, 'text/plain');

        $this->mailer->send($msg);
    }

    public function sendAuthCode(TwoFactorInterface $user): void
    {
        $this->sendAuthCodeToMail(
            'Confirm authentication',
            'You verification code:',
            $user
        );
    }

    public function sendAuthCodeToMail(
        string $subject,
        string $label,
        TwoFactorInterface $user
    ): void {
        $body = $this->twigEngine->render('mail/auth_verification_code.html.twig', [
            'label' => $label,
            'email' => $user->getEmailAuthRecipient(),
            'code' => $user->getEmailAuthCode(),
        ]);

        $textBody = $this->twigEngine->render('mail/auth_verification_code.txt.twig', [
            'label' => $label,
            'email' => $user->getEmailAuthRecipient(),
            'code' => $user->getEmailAuthCode(),
        ]);


        $msg = (new Swift_Message($subject))
            ->setFrom([$this->mail => 'Mintme'])
            ->setTo($user->getEmailAuthRecipient())
            ->setBody($body, 'text/html')
            ->addPart($textBody, 'text/plain');

        $this->mailer->send($msg);
    }

    public function sendTransactionCompletedMail(TradebleInterface $tradable, User $user, string $amount, string $transactionType): void
    {
        $body = $this->twigEngine->render("mail/{$transactionType}_completed.html.twig", [
            'username' => $user->getUsername(),
            'tradable' => $tradable,
            'amount' => $amount,
        ]);

        $textBody = $this->twigEngine->render("mail/{$transactionType}_completed.txt.twig", [
            'username' => $user->getUsername(),
            'tradable' => $tradable,
            'amount' => $amount,
        ]);

        $msg = (new Swift_Message(ucfirst($transactionType)." Completed"))
            ->setFrom([$this->mail => 'Mintme'])
            ->setTo($user->getEmail())
            ->setBody($body, 'text/html')
            ->addPart($textBody, 'text/plain');

        $this->mailer->send($msg);
    }

    public function checkConnection(): void
    {
        $transport = $this->mailer->getTransport();

        if (!$transport->ping()) {
            $transport->stop();
            $transport->start();
        }
    }
}
