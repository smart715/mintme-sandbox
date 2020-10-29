<?php declare(strict_types = 1);

namespace App\Mailer;

use App\Entity\PendingWithdrawInterface;
use App\Entity\Profile;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Entity\UserLoginInfo;
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

    public function sendTransactionCompletedMail(User $user, string $transactionType): void
    {
        $confirmLink = $this->urlGenerator->generate(
            'wallet',
            ['tab' => 'dw-history'],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $body = $this->twigEngine->render("mail/{$transactionType}_completed.html.twig", [
            'username' => $user->getUsername(),
            'urlWallet' => $confirmLink,
        ]);

        $textBody = $this->twigEngine->render("mail/{$transactionType}_completed.txt.twig", [
            'username' => $user->getUsername(),
            'urlWallet' => $confirmLink,
        ]);

        $msg = (new Swift_Message(ucfirst($transactionType)." Completed"))
            ->setFrom([$this->mail => 'Mintme'])
            ->setTo($user->getEmail())
            ->setBody($body, 'text/html')
            ->addPart($textBody, 'text/plain');

        $this->mailer->send($msg);
    }

    public function sendPasswordResetMail(User $user, bool $resetting): void
    {
        $body = $this->twigEngine->render("mail/password_reset.html.twig", [
            'username' => $user->getUsername(),
            'resetting' => $resetting,
        ]);

        $textBody = $this->twigEngine->render("mail/password_reset.txt.twig", [
            'username' => $user->getUsername(),
            'resetting' => $resetting,
        ]);

        $msg = (new Swift_Message("Your password has been ".($resetting ? "reset" : "changed")))
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

    public function sendTokenDeletedMail(Token $token): void
    {
        $user = $token->getProfile()->getUser();

        $body = $this->twigEngine->render("mail/token_deleted.html.twig", [
            'username' => $user->getUsername(),
            'tokenName' => $token->getName(),
        ]);

        $textBody = $this->twigEngine->render("mail/token_deleted.txt.twig", [
            'username' => $user->getUsername(),
            'tokenName' => $token->getName(),
        ]);

        $msg = (new Swift_Message("Token Deleted"))
            ->setFrom([$this->mail => 'Mintme'])
            ->setTo($user->getEmail())
            ->setBody($body, 'text/html')
            ->addPart($textBody, 'text/plain');

        $this->mailer->send($msg);
    }

    public function sendNewDeviceDetectedMail(User $user, UserLoginInfo $userDeviceInfo): void
    {
        $message = 'Our system has detected a new login attempt from a new IP address or device.';
        $body = $this->twigEngine->render('mail/new_device_detected.html.twig', [
            'message' => $message,
            'username' => $user->getUsername(),
            'user_device_info' => $userDeviceInfo,
        ]);

        $textBody = $this->twigEngine->render('mail/new_device_detected.txt.twig', [
            'message' => $message,
            'username' => $user->getUsername(),
            'user_device_info' => $userDeviceInfo,
        ]);

        $subjectMsg = 'New login attempt from a new IP address or device';
        $msg = (new Swift_Message($subjectMsg))
            ->setFrom([$this->mail => 'Mintme'])
            ->setTo($user->getEmail())
            ->setBody($body, 'text/html')
            ->addPart($textBody, 'text/plain');

        $this->mailer->send($msg);
    }

    public function sendProfileFillingReminderMail(User $user): void
    {
        $body = $this->twigEngine->render('mail/profile_reminder.html.twig', [
            'username' => $user->getUsername(),
            'profile_name' => $user->getProfile()->getNickname(),
        ]);

        $textBody = $this->twigEngine->render('mail/profile_reminder.txt.twig', [
            'username' => $user->getUsername(),
            'profile_name' => $user->getProfile()->getNickname(),
        ]);

        $subjectMsg = 'Mintme Reminder';
        $msg = (new Swift_Message($subjectMsg))
            ->setFrom([$this->mail => 'Mintme'])
            ->setTo($user->getEmail())
            ->setBody($body, 'text/html')
            ->addPart($textBody, 'text/plain');
        $this->mailer->send($msg);
    }

    public function sendTokenDescriptionReminderMail(User $user): void
    {
        $body = $this->twigEngine->render('mail/token_description_reminder.html.twig', [
            'username' => $user->getUsername(),
            'token_name' => $user->getProfile()->getToken()->getName(),
        ]);

        $textBody = $this->twigEngine->render('mail/token_description_reminder.txt.twig', [
            'username' => $user->getUsername(),
            'token_name' => $user->getProfile()->getToken()->getName(),
        ]);

        $subjectMsg = 'Mintme Reminder';
        $msg = (new Swift_Message($subjectMsg))
            ->setFrom([$this->mail => 'Mintme'])
            ->setTo($user->getEmail())
            ->setBody($body, 'text/html')
            ->addPart($textBody, 'text/plain');
        $this->mailer->send($msg);
    }

    public function sendNewInvestorMail(User $user, string $newInvestor): void
    {
        $body = $this->twigEngine->render("mail/new_investor.html.twig", [
            'username' => $user->getUsername(),
            'investorProfile' => $newInvestor,
            'userTokenName' => $user->getProfile()->getToken()->getName(),
        ]);

        $textBody = $this->twigEngine->render("mail/new_investor.txt.twig", [
            'username' => $user->getUsername(),
            'investorProfile' => $newInvestor,
            'userTokenName' => $user->getProfile()->getToken()->getName(),
        ]);

        $msg = (new Swift_Message('New Investor'))
            ->setFrom([$this->mail => 'Mintme'])
            ->setTo($user->getEmail())
            ->setBody($body, 'text/html')
            ->addPart($textBody, 'text/plain');

        $this->mailer->send($msg);
    }

    public function sendNewPostMail(User $user, String $tokenName): void
    {
        $body = $this->twigEngine->render("mail/new_post.html.twig", [
            'username' => $user->getUsername(),
            'tokenName' => $tokenName,
        ]);

        $textBody = $this->twigEngine->render("mail/new_post.txt.twig", [
            'username' => $user->getUsername(),
            'tokenName' => $tokenName,
        ]);

        $msg = (new Swift_Message('New Post'))
            ->setFrom([$this->mail => 'Mintme'])
            ->setTo($user->getEmail())
            ->setBody($body, 'text/html')
            ->addPart($textBody, 'text/plain');

        $this->mailer->send($msg);
    }

    public function sendTokenDeployedMail(User $user, String $tokenName): void
    {
        $body = $this->twigEngine->render("mail/new_token_deployed.html.twig", [
            'username' => $user->getUsername(),
            'tokenName' => $tokenName,
        ]);

        $textBody = $this->twigEngine->render("mail/new_token_deployed.txt.twig", [
            'username' => $user->getUsername(),
            'tokenName' => $tokenName,
        ]);

        $msg = (new Swift_Message('New Token Deployed'))
            ->setFrom([$this->mail => 'Mintme'])
            ->setTo($user->getEmail())
            ->setBody($body, 'text/html')
            ->addPart($textBody, 'text/plain');

        $this->mailer->send($msg);
    }

    public function sendNoOrdersMail(User $user, String $tokenName): void
    {
        $body = $this->twigEngine->render("mail/no_orders.html.twig", [
            'username' => $user->getUsername(),
            'tokenName' => $tokenName,
        ]);

        $textBody = $this->twigEngine->render("mail/no_orders.txt.twig", [
            'username' => $user->getUsername(),
            'tokenName' => $tokenName,
        ]);

        $msg = (new Swift_Message('Orders'))
        ->setFrom([$this->mail => 'Mintme'])
            ->setTo($user->getEmail())
            ->setBody($body, 'text/html')
            ->addPart($textBody, 'text/plain');

        $this->mailer->send($msg);
    }

    public function sendKnowledgeBaseMail(User $user, Token $token): void
    {
        $tokenSalesUrl = $this->urlGenerator->generate(
            'kb_show',
            ['url' => 'Time-for-token-sales-how-can-I-make-a-difference'],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $aimingUrl = $this->urlGenerator->generate(
            'kb_show',
            ['url' => 'Aiming-at-a-strong-token'],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $ideasUrl = $this->urlGenerator->generate(
            'kb_show',
            ['url' => 'Ideas-to-promote-and-sell-your-token'],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $stuckUrl = $this->urlGenerator->generate(
            'kb_show',
            ['url' => 'Stuck-not-knowing-what-to-do-next'],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $talkingUrl = $this->urlGenerator->generate(
            'kb_show',
            ['url' => 'Talking-to-your-followers-about-MintMe-we-got-some-ideas'],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $deployUrl = $this->urlGenerator->generate(
            'kb_show',
            ['url' => 'How-to-deploy-my-token-to-the-blockchain'],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $body = $this->twigEngine->render("mail/knowledge_base_suggestions.html.twig", [
            'username' => $user->getUsername(),
            'tokenName' => $token->getName(),
            'tokenSalesUrl' => $tokenSalesUrl,
            'aimingUrl' => $aimingUrl,
            'ideasUrl' => $ideasUrl,
            'stuckUrl' => $stuckUrl,
            'talkingUrl' => $talkingUrl,
            'deployUrl' => $deployUrl,
        ]);

        $textBody = $this->twigEngine->render("mail/knowledge_base_suggestions.txt.twig", [
            'username' => $user->getUsername(),
            'tokenName' => $token->getName(),
        ]);

        $subjectMsg = 'What Now?';
        $msg = (new Swift_Message($subjectMsg))
            ->setFrom([$this->mail => 'Mintme'])
            ->setTo($user->getEmail())
            ->setBody($body, 'text/html')
            ->addPart($textBody, 'text/plain');

        $this->mailer->send($msg);
    }
}
