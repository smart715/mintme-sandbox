<?php declare(strict_types = 1);

namespace App\Mailer;

use App\Entity\PendingWithdraw;
use App\Entity\User;
use Swift_Mailer;
use Swift_Message;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Templating\EngineInterface;

class Mailer implements MailerInterface
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

    public function sendWithdrawConfirmationMail(User $user, PendingWithdraw $withdrawData): void
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

        $msg = (new Swift_Message('Confirm withdraw'))
            ->setFrom([$this->mail => 'Mintme'])
            ->setTo($user->getEmail())
            ->setBody($body)
            ->setContentType('text/html');

        $this->mailer->send($msg);
    }
}
