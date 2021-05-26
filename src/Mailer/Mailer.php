<?php declare(strict_types = 1);

namespace App\Mailer;

use App\Entity\AirdropCampaign\AirdropReferralCode;
use App\Entity\PendingWithdrawInterface;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Entity\UserLoginInfo;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use Money\Money;
use Scheb\TwoFactorBundle\Mailer\AuthCodeMailerInterface;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;
use Swift_Mailer;
use Swift_Message;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

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

    private TranslatorInterface $translator;

    private MoneyWrapperInterface $moneyWrapper;

    public function __construct(
        string $mail,
        Swift_Mailer $mailer,
        EngineInterface $twigEngine,
        UrlGeneratorInterface $urlGenerator,
        TranslatorInterface $translator,
        MoneyWrapperInterface $moneyWrapper
    ) {
        $this->mail = $mail;
        $this->mailer = $mailer;
        $this->twigEngine = $twigEngine;
        $this->urlGenerator = $urlGenerator;
        $this->translator = $translator;
        $this->moneyWrapper = $moneyWrapper;
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

        $subject = $this->translator->trans('email.confirm_withdraw');
        $msg = (new Swift_Message($subject))
            ->setFrom([$this->mail => 'Mintme'])
            ->setTo($user->getEmail())
            ->setBody($body, 'text/html')
            ->addPart($textBody, 'text/plain');

        $this->mailer->send($msg);
    }

    public function sendAuthCode(TwoFactorInterface $user): void
    {
        $this->sendAuthCodeToMail(
            $this->translator->trans('email.confirm_authentication'),
            $this->translator->trans('email.verification_code'),
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

        $subject = ucfirst($transactionType).' '.$this->translator->trans('email.completed');
        $msg = (new Swift_Message($subject))
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

        $subject = 'email.password_changed';

        if ($resetting) {
            $subject = 'email.password_reset';
        }

        $subject = $this->translator->trans($subject);

        $msg = (new Swift_Message($subject))
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

        $subject = $this->translator->trans('email.token_deleted');
        $msg = (new Swift_Message($subject))
            ->setFrom([$this->mail => 'Mintme'])
            ->setTo($user->getEmail())
            ->setBody($body, 'text/html')
            ->addPart($textBody, 'text/plain');

        $this->mailer->send($msg);
    }

    public function sendNewDeviceDetectedMail(User $user, UserLoginInfo $userDeviceInfo): void
    {
        $message = $this->translator->trans('new_device.detected.msg');

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

        $subject = $this->translator->trans('email.new_login_from_new_ip');
        $msg = (new Swift_Message($subject))
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

        $subject = $this->translator->trans('email.mintme_reminder');
        $msg = (new Swift_Message($subject))
            ->setFrom([$this->mail => 'Mintme'])
            ->setTo($user->getEmail())
            ->setBody($body, 'text/html')
            ->addPart($textBody, 'text/plain');
        $this->mailer->send($msg);
    }

    public function sendTokenDescriptionReminderMail(Token $token): void
    {
        $body = $this->twigEngine->render('mail/token_description_reminder.html.twig', [
            'username' => $token->getOwner()->getUsername(),
            'tokenName' => $token->getName(),
        ]);

        $textBody = $this->twigEngine->render('mail/token_description_reminder.txt.twig', [
            'username' => $token->getOwner()->getUsername(),
            'tokenName' => $token->getName(),
        ]);

        $subject = $this->translator->trans('email.mintme_reminder');
        $msg = (new Swift_Message($subject))
            ->setFrom([$this->mail => 'Mintme'])
            ->setTo($token->getOwner()->getEmail())
            ->setBody($body, 'text/html')
            ->addPart($textBody, 'text/plain');
        $this->mailer->send($msg);
    }

    public function sendNewInvestorMail(Token $token, string $newInvestor): void
    {
        $body = $this->twigEngine->render("mail/new_investor.html.twig", [
            'username' => $token->getOwner()->getUsername(),
            'investorProfile' => $newInvestor,
            'userTokenName' => $token->getName(),
        ]);

        $textBody = $this->twigEngine->render("mail/new_investor.txt.twig", [
            'username' => $token->getOwner()->getUsername(),
            'investorProfile' => $newInvestor,
            'userTokenName' => $token->getName(),
        ]);

        $subject = $this->translator->trans('email.new_investor');
        $msg = (new Swift_Message($subject))
            ->setFrom([$this->mail => 'Mintme'])
            ->setTo($token->getOwner()->getEmail())
            ->setBody($body, 'text/html')
            ->addPart($textBody, 'text/plain');

        $this->mailer->send($msg);
    }

    public function sendNewPostMail(User $user, String $tokenName, String $slug): void
    {
        $body = $this->twigEngine->render("mail/new_post.html.twig", [
            'username' => $user->getUsername(),
            'tokenName' => $tokenName,
            'slug' => $slug,
        ]);

        $textBody = $this->twigEngine->render("mail/new_post.txt.twig", [
            'username' => $user->getUsername(),
            'tokenName' => $tokenName,
            'slug' => $slug,
        ]);

        $subject = $this->translator->trans('email.new_post');
        $msg = (new Swift_Message($subject))
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

        $subject = $this->translator->trans('email.new_token_deployed');
        $msg = (new Swift_Message($subject))
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

        $subject = $this->translator->trans('email.orders');
        $msg = (new Swift_Message($subject))
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
            'tokenSalesUrl' => $tokenSalesUrl,
            'aimingUrl' => $aimingUrl,
            'ideasUrl' => $ideasUrl,
            'stuckUrl' => $stuckUrl,
            'talkingUrl' => $talkingUrl,
            'deployUrl' => $deployUrl,
        ]);

        $subject = $this->translator->trans('email.what_now');
        $msg = (new Swift_Message($subject))
            ->setFrom([$this->mail => 'Mintme'])
            ->setTo($user->getEmail())
            ->setBody($body, 'text/html')
            ->addPart($textBody, 'text/plain');

        $this->mailer->send($msg);
    }

    public function sendTokenMarketingTipMail(User $user, String $kbLink): void
    {
        $pieces = explode('-', $kbLink);
        $title = implode(' ', $pieces);

        $url = $this->urlGenerator->generate(
            'kb_show',
            ['url' => $kbLink],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $body = $this->twigEngine->render("mail/token_marketing_tips.html.twig", [
            'username' => $user->getUsername(),
            'url' => $url,
            'title' => $title,
        ]);

        $textBody = $this->twigEngine->render("mail/token_marketing_tips.txt.twig", [
            'username' => $user->getUsername(),
            'url' => $url,
            'title' => $title,
        ]);
        $subject = $this->translator->trans('userNotification.type.token_marketing_tips');
        $msg = (new Swift_Message($subject))
            ->setFrom([$this->mail => 'Mintme'])
            ->setTo($user->getEmail())
            ->setBody($body, 'text/html')
            ->addPart($textBody, 'text/plain');

        $this->mailer->send($msg);
    }

    public function sendAirdropFeatureMail(Token $token): void
    {
        $modalUrl = $this->urlGenerator->generate(
            'token_show',
            [
                'name' => $token->getName(),
                'modal' => 'settings',
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $body = $this->twigEngine->render('mail/airdrop_feature.html.twig', [
            'nickname' => $token->getProfile()->getNickname(),
            'modalUrl' => $modalUrl,
        ]);

        $textBody = $this->twigEngine->render('mail/airdrop_feature.txt.twig', [
            'nickname' => $token->getProfile()->getNickname(),
            'modalUrl' => $modalUrl,
        ]);

        $subject = $this->translator->trans('mail.airdrop_feature.subject');
        $msg = (new Swift_Message($subject))
            ->setFrom([$this->mail => 'Mintme'])
            ->setTo($token->getProfile()->getUser()->getEmail())
            ->setBody($body, 'text/html')
            ->addPart($textBody, 'text/plain');

        $this->mailer->send($msg);
    }

    public function sendMintmeHostMail(User $user, string $price, string $freeDays, string $mintmeHostPath): void
    {
        $body = $this->twigEngine->render("mail/mintme_host.html.twig", [
            'username' => $user->getUsername(),
            'freeDays' => $freeDays,
            'price' => $price,
            'mintmeHostPath' => $mintmeHostPath,
        ]);

        $textBody = $this->twigEngine->render("mail/mintme_host.txt.twig", [
            'username' => $user->getUsername(),
            'freeDays' => $freeDays,
            'price' => $price,
            'mintmeHostPath' => $mintmeHostPath,
        ]);

        $subject = $this->translator->trans('mail.mintme_host_subject');
        $msg = (new Swift_Message($subject))
            ->setFrom([$this->mail => 'Mintme'])
            ->setTo($user->getEmail())
            ->setBody($body, 'text/html')
            ->addPart($textBody, 'text/plain');

        $this->mailer->send($msg);
    }

    public function sendAirdropClaimedMail(
        User $user,
        Token $token,
        Money $airdropReward,
        string $airdropReferralCode
    ): void {
        $tokenPostsLink = $this->urlGenerator->generate(
            'new_show_post',
            [
                'name' => $token->getName(),
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $AirdropReferralLink = $this->urlGenerator->generate(
            'airdrop_referral',
            [
                'name' => $token->getName(),
                'hash' => $airdropReferralCode,
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $body = $this->twigEngine->render("mail/airdrop_claimed.html.twig", [
            'username' => $user->getUsername(),
            'airdropReward' => $this->moneyWrapper->format($airdropReward),
            'tokenName' => $token->getName(),
            'tokenPostsLink' => $tokenPostsLink,
            'airdropReferralLink' => $AirdropReferralLink,
            'tokenSubunit' => Token::TOKEN_SUBUNIT,
        ]);

        $textBody = $this->twigEngine->render("mail/airdrop_claimed.txt.twig", [
            'username' => $user->getUsername(),
            'airdropReward' => $this->moneyWrapper->format($airdropReward),
            'tokenName' => $token->getName(),
            'tokenPostsLink' => $tokenPostsLink,
            'airdropReferralLink' => $AirdropReferralLink,
            'tokenSubunit' => Token::TOKEN_SUBUNIT,
        ]);

        $subject = $this->translator->trans('mail.airdrop_claimed.subject');
        $msg = (new Swift_Message($subject))
            ->setFrom([$this->mail => 'Mintme'])
            ->setTo($user->getEmail())
            ->setBody($body, 'text/html')
            ->addPart($textBody, 'text/plain');

        $this->mailer->send($msg);
    }

    public function sendOwnTokenDeployedMail(User $user, string $tokenName, string $txHash): void
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

        $body = $this->twigEngine->render("mail/token_deployed.html.twig", [
            'username' => $user->getUsername(),
            'tokenName' => $tokenName,
            'txHash' => $txHash,
            'tokenSalesUrl' => $tokenSalesUrl,
            'aimingUrl' => $aimingUrl,
            'ideasUrl' => $ideasUrl,
            'stuckUrl' => $stuckUrl,
            'talkingUrl' => $talkingUrl,
        ]);

        $textBody = $this->twigEngine->render("mail/token_deployed.txt.twig", [
            'username' => $user->getUsername(),
            'tokenName' => $tokenName,
            'txHash' => $txHash,
            'tokenSalesUrl' => $tokenSalesUrl,
            'aimingUrl' => $aimingUrl,
            'ideasUrl' => $ideasUrl,
            'stuckUrl' => $stuckUrl,
            'talkingUrl' => $talkingUrl,
        ]);

        $subject = $this->translator->trans('mail.token_deployed.subject');
        $msg = (new Swift_Message($subject))
            ->setFrom([$this->mail => 'Mintme'])
            ->setTo($user->getEmail())
            ->setBody($body, 'text/html')
            ->addPart($textBody, 'text/plain');

        $this->mailer->send($msg);
    }
}
