<?php declare(strict_types = 1);

namespace App\Mailer;

use App\Communications\DeployCostFetcher;
use App\Config\FailedLoginConfig;
use App\Entity\Crypto;
use App\Entity\DeployNotification;
use App\Entity\PendingWithdrawInterface;
use App\Entity\Token\Token;
use App\Entity\Token\TokenDeploy;
use App\Entity\TradableInterface;
use App\Entity\User;
use App\Entity\UserLoginInfo;
use App\Exchange\Config\DeployCostConfig;
use App\Services\TranslatorService\TranslatorInterface;
use App\SmartContract\Config\ExplorerUrlsConfigInterface;
use App\Utils\Symbols;
use App\Wallet\Model\LackMainBalanceReport;
use App\Wallet\Money\MoneyWrapperInterface;
use FOS\UserBundle\Model\UserInterface;
use Money\Money;
use Scheb\TwoFactorBundle\Mailer\AuthCodeMailerInterface;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Templating\EngineInterface;
use Twig\Environment;

class Mailer implements MailerInterface, AuthCodeMailerInterface, \FOS\UserBundle\Mailer\MailerInterface
{
    protected string $mail;
    protected string $mailName;
    protected \Symfony\Component\Mailer\MailerInterface $mailer;
    protected EngineInterface $twigEngine;
    protected UrlGeneratorInterface $urlGenerator;
    private TranslatorInterface $translator;
    private MoneyWrapperInterface $moneyWrapper;
    private ExplorerUrlsConfigInterface $explorerUrlConfig;
    private FailedLoginConfig $failedLoginConfig;
    private string $registrationTemplate;
    private string $resettingTemplate;
    private Environment $environment;
    private DeployCostConfig $deployCostConfig;

    public function __construct(
        string $mail,
        string $mailName,
        string $registrationTemplate,
        string $resettingTemplate,
        \Symfony\Component\Mailer\MailerInterface $mailer,
        EngineInterface $twigEngine,
        UrlGeneratorInterface $urlGenerator,
        TranslatorInterface $translator,
        MoneyWrapperInterface $moneyWrapper,
        ExplorerUrlsConfigInterface $explorerUrlConfig,
        FailedLoginConfig $failedLoginConfig,
        Environment $environment,
        DeployCostConfig $deployCostConfig
    ) {
        $this->mail = $mail;
        $this->mailName = $mailName;
        $this->registrationTemplate = $registrationTemplate;
        $this->resettingTemplate = $resettingTemplate;
        $this->mailer = $mailer;
        $this->twigEngine = $twigEngine;
        $this->urlGenerator = $urlGenerator;
        $this->translator = $translator;
        $this->moneyWrapper = $moneyWrapper;
        $this->explorerUrlConfig = $explorerUrlConfig;
        $this->failedLoginConfig = $failedLoginConfig;
        $this->environment = $environment;
        $this->deployCostConfig = $deployCostConfig;
    }

    public function sendDeployNotificationMail(DeployNotification $deployNotification): void
    {
        $userNotifier = $deployNotification->getNotifier();
        $token = $deployNotification->getToken();
        /** @var User $tokenOwner */
        $tokenOwner = $token->getOwner();
        $price = $this->deployCostConfig->getDeployCost(Symbols::WEB);

        $this->translator->setLocale($tokenOwner->getLocale());

        $deployUrl = $this->urlGenerator->generate(
            'token_settings',
            [
                'tokenName' => $token->getName(),
                'tab' => 'deploy',
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $data = [
            'notifierNickname' => $userNotifier->getNickname(),
            'tokenOwnerNickname' => $tokenOwner->getNickname(),
            'usdDeployPrice' => $price,
            'deployUrl' => $deployUrl,
        ];

        $body = $this->twigEngine->render('mail/deploy_user_notification.html.twig', $data);
        $textBody = $this->twigEngine->render('mail/deploy_user_notification.txt.twig', $data);

        $subject = $this->translator->trans(
            'email.deploy_notification.subject',
            [
                '%nickname%' => $userNotifier->getNickname(),
            ]
        );

        $msg = (new Email())
            ->subject($subject)
            ->from(new Address($this->mail, $this->mailName))
            ->to($tokenOwner->getEmail())
            ->html($body)
            ->text($textBody);

        $this->mailer->send($msg);
    }

    public function sendLackBalanceReportMail(
        string $mail,
        LackMainBalanceReport $report
    ): void {
        $user = $report->getUser();
        $tradableSymbol = $report->getTradable()->getSymbol();
        $cryptoNetworkSymbol = $report->getCryptoNetwork()->getSymbol();

        $isWMM = Symbols::WEB === $tradableSymbol && Symbols::WEB !== $cryptoNetworkSymbol;

        $data = [
            'username' => $user->getNickname(),
            'email' => $user->getEmail(),
            'currency' => $tradableSymbol,
            'blockchain' => $cryptoNetworkSymbol,
            'nativeMoneySymbol' => $report->getNativeMoneyCrypto()
                ? $report->getNativeMoneyCrypto()->getSymbol()
                : $cryptoNetworkSymbol,
            'isToken' => $report->isToken(),
            'isWMM' => $isWMM,
            'action' => $report->getType()->getTypeCode(),
            'amount' => $this->moneyWrapper->format($report->getAmount(), false),
            'currencyBalance' => $this->moneyWrapper->format($report->getTradableBalance(), false),
            'currencyAmount' => $this->moneyWrapper->format($report->getTradableAmount(), false),
            'blockchainBalance' => $this->moneyWrapper->format($report->getNetworkBalance(), false),
            'blockchainAmount' => $this->moneyWrapper->format($report->getNetworkAmount(), false),
        ];

        $body = $this->twigEngine->render('mail/lack_balance_report.html.twig', $data);
        $textBody = $this->twigEngine->render('mail/lack_balance_report.txt.twig', $data);
        $subject = 'Lack of balance in gateway wallet';

        $msg = (new Email())
            ->subject($subject)
            ->from(new Address($this->mail, $this->mailName))
            ->to($mail)
            ->html($body)
            ->text($textBody);

        $this->mailer->send($msg);
    }

    public function sendTransactionDelayedMail(User $user): void
    {
        $this->translator->setLocale($user->getLocale());

        $urlWallet = $this->urlGenerator->generate(
            'wallet',
            ['tab' => 'dw-history'],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $data = [
            'nickname' => $user->getNickname(),
            'urlWallet' => $urlWallet,
        ];

        $body = $this->twigEngine->render('mail/transaction_delayed.html.twig', $data);
        $textBody = $this->twigEngine->render('mail/transaction_delayed.txt.twig', $data);

        $subject = $this->translator->trans('email.transaction_delayed');

        $msg = (new Email())
            ->subject($subject)
            ->from(new Address($this->mail, $this->mailName))
            ->to($user->getEmail())
            ->html($body)
            ->text($textBody);

        $this->mailer->send($msg);
    }

    public function sendWithdrawConfirmationMail(
        User $user,
        PendingWithdrawInterface $withdrawData,
        TradableInterface $tradable,
        string $cryptoNetworkName
    ): void {
        $this->translator->setLocale($user->getLocale());

        $confirmLink = $this->urlGenerator->generate(
            'withdraw-confirm',
            ['hash' => $withdrawData->getHash()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $amount = $this->moneyWrapper->format($withdrawData->getAmount()->getAmount(), false);
        $address = $withdrawData->getAddress()->getAddress();

        $body = $this->twigEngine->render('mail/withdraw_accept.html.twig', [
            'user' => $user,
            'nickname' => $user->getNickname(),
            'confirmationUrl' => $confirmLink,
            'amount' => $amount,
            'address' => $address,
            'blockchain' => $cryptoNetworkName,
            'currency' => $tradable->getSymbol(),
        ]);

        $textBody = $this->twigEngine->render('mail/withdraw_accept.txt.twig', [
            'user' => $user,
            'nickname' => $user->getNickname(),
            'confirmationUrl' => $confirmLink,
            'amount' => $amount,
            'address' => $address,
            'blockchain' => $cryptoNetworkName,
            'currency' => $tradable->getSymbol(),
        ]);

        $subject = $this->translator->trans('email.confirm_withdraw');
        $msg = (new Email())
            ->subject($subject)
            ->from(new Address($this->mail, $this->mailName))
            ->to($user->getEmail())
            ->html($body)
            ->text($textBody);

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
            'user' => $user,
        ]);

        $textBody = $this->twigEngine->render('mail/auth_verification_code.txt.twig', [
            'label' => $label,
            'email' => $user->getEmailAuthRecipient(),
            'code' => $user->getEmailAuthCode(),
            'user' => $user,
        ]);

        $msg = (new Email())
            ->subject($subject)
            ->from(new Address($this->mail, $this->mailName))
            ->to($user->getEmailAuthRecipient())
            ->html($body)
            ->text($textBody);

        $this->mailer->send($msg);
    }

    public function sendTransactionCompletedMail(
        User $user,
        TradableInterface $tradable,
        Money $amount,
        string $address,
        string $transactionType,
        string $cryptoNetworkName
    ): void {
        $this->translator->setLocale($user->getLocale());

        $confirmLink = $this->urlGenerator->generate(
            'wallet',
            ['tab' => 'dw-history'],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $amount = $this->moneyWrapper->format($amount, false);

        $body = $this->twigEngine->render("mail/{$transactionType}_completed.html.twig", [
            'nickname' => $user->getNickname(),
            'urlWallet' => $confirmLink,
            'amount' => $amount,
            'address' => $address,
            'blockchain' => $cryptoNetworkName,
            'currency' => $tradable->getSymbol(),
        ]);

        $textBody = $this->twigEngine->render("mail/{$transactionType}_completed.txt.twig", [
            'nickname' => $user->getNickname(),
            'urlWallet' => $confirmLink,
            'amount' => $amount,
            'address' => $address,
            'blockchain' => $cryptoNetworkName,
            'currency' => $tradable->getSymbol(),
        ]);

        $subject = $this->translator->trans('email.subject.' . $transactionType . '.completed');
        $msg = (new Email())
            ->subject($subject)
            ->from(new Address($this->mail, $this->mailName))
            ->to($user->getEmail())
            ->html($body)
            ->text($textBody);

        $this->mailer->send($msg);
    }

    public function sendPasswordResetMail(User $user, bool $resetting): void
    {
        $this->translator->setLocale($user->getLocale());

        $body = $this->twigEngine->render("mail/password_reset.html.twig", [
            'nickname' => $user->getNickname(),
            'resetting' => $resetting,
        ]);

        $textBody = $this->twigEngine->render("mail/password_reset.txt.twig", [
            'nickname' => $user->getNickname(),
            'resetting' => $resetting,
        ]);

        $subject = 'email.password_changed';

        if ($resetting) {
            $subject = 'email.password_reset';
        }

        $subject = $this->translator->trans($subject);

        $msg = (new Email())
            ->subject($subject)
            ->from(new Address($this->mail, $this->mailName))
            ->to($user->getEmail())
            ->html($body)
            ->text($textBody);

        $this->mailer->send($msg);
    }

    public function sendTokenDeletedMail(Token $token): void
    {
        $this->translator->setLocale($token->getOwner()->getLocale());

        $user = $token->getProfile()->getUser();

        $body = $this->twigEngine->render("mail/token_deleted.html.twig", [
            'nickname' => $user->getNickname(),
            'tokenName' => $token->getName(),
        ]);

        $textBody = $this->twigEngine->render("mail/token_deleted.txt.twig", [
            'nickname' => $user->getNickname(),
            'tokenName' => $token->getName(),
        ]);

        $subject = $this->translator->trans('email.token_deleted');
        $msg = (new Email())
            ->subject($subject)
            ->from(new Address($this->mail, $this->mailName))
            ->to($user->getEmail())
            ->html($body)
            ->text($textBody);

        $this->mailer->send($msg);
    }

    public function sendNewDeviceDetectedMail(User $user, UserLoginInfo $userDeviceInfo): void
    {
        $this->translator->setLocale($user->getLocale());

        $message = $this->translator->trans('new_device.detected.msg');

        $body = $this->twigEngine->render('mail/new_device_detected.html.twig', [
            'message' => $message,
            'nickname' => $user->getNickname(),
            'user_device_info' => $userDeviceInfo,
        ]);

        $textBody = $this->twigEngine->render('mail/new_device_detected.txt.twig', [
            'message' => $message,
            'nickname' => $user->getNickname(),
            'user_device_info' => $userDeviceInfo,
        ]);

        $subject = $this->translator->trans('email.new_login_from_new_ip');
        $msg = (new Email())
            ->subject($subject)
            ->from(new Address($this->mail, $this->mailName))
            ->to($user->getEmail())
            ->html($body)
            ->text($textBody);

        $this->mailer->send($msg);
    }

    public function sendProfileFillingReminderMail(User $user): void
    {
        $this->translator->setLocale($user->getLocale());

        $body = $this->twigEngine->render('mail/profile_reminder.html.twig', [
            'nickname' => $user->getNickname(),
            'profile_name' => $user->getProfile()->getNickname(),
        ]);

        $textBody = $this->twigEngine->render('mail/profile_reminder.txt.twig', [
            'nickname' => $user->getNickname(),
            'profile_name' => $user->getProfile()->getNickname(),
        ]);

        $subject = $this->translator->trans('email.mintme_reminder');
        $msg = (new Email())
            ->subject($subject)
            ->from(new Address($this->mail, $this->mailName))
            ->to($user->getEmail())
            ->html($body)
            ->text($textBody);
        $this->mailer->send($msg);
    }

    public function sendTokenDescriptionReminderMail(Token $token): void
    {
        $this->translator->setLocale($token->getOwner()->getLocale());

        $body = $this->twigEngine->render('mail/token_description_reminder.html.twig', [
            'nickname' => $token->getOwner()->getNickname(),
            'tokenName' => $token->getName(),
        ]);

        $textBody = $this->twigEngine->render('mail/token_description_reminder.txt.twig', [
            'nickname' => $token->getOwner()->getNickname(),
            'tokenName' => $token->getName(),
        ]);

        $subject = $this->translator->trans('email.mintme_reminder');
        $msg = (new Email())
            ->subject($subject)
            ->from(new Address($this->mail, $this->mailName))
            ->to($token->getOwner()->getEmail())
            ->html($body)
            ->text($textBody);
        $this->mailer->send($msg);
    }

    public function sendNewInvestorMail(Token $token, string $newInvestor, ?string $marketSymbol): void
    {
        $this->translator->setLocale($token->getOwner()->getLocale());

        $body = $this->twigEngine->render("mail/new_investor.html.twig", [
            'nickname' => $token->getOwner()->getNickname(),
            'investorProfile' => $newInvestor,
            'userTokenName' => $token->getName(),
            'marketSymbol' => $marketSymbol ?? Symbols::MINTME,
        ]);

        $textBody = $this->twigEngine->render("mail/new_investor.txt.twig", [
            'nickname' => $token->getOwner()->getNickname(),
            'investorProfile' => $newInvestor,
            'userTokenName' => $token->getName(),
            'marketSymbol' => $marketSymbol ?? Symbols::MINTME,
        ]);

        $subject = $this->translator->trans('email.new_investor');
        $msg = (new Email())
            ->subject($subject)
            ->from(new Address($this->mail, $this->mailName))
            ->to($token->getOwner()->getEmail())
            ->html($body)
            ->text($textBody);

        $this->mailer->send($msg);
    }

    public function sendGroupedRewardsMail(User $user, String $tokenName, array $rewards, string $type): void
    {
        $this->translator->setLocale($user->getLocale());

        $isBounty = "bounty" === $type;

        $body = $this->twigEngine->render("mail/grouped_rewards.html.twig", [
            'username' => $user->getUsername(),
            'tokenName' => $tokenName,
            'isBounty' => $isBounty,
            'rewards' => $rewards,
        ]);

        $textBody = $this->twigEngine->render("mail/grouped_rewards.txt.twig", [
            'username' => $user,
            'tokenName' => $tokenName,
            'isBounty' => $isBounty,
            'rewards' => $rewards,
        ]);

        $subject = $this->translator->trans(
            $isBounty ? 'email.grouped_bounties_subject' : 'email.grouped_rewards_subject',
            [
                '%number%' => count($rewards),
                '%tokenName%' => $tokenName,
            ],
        );

        $msg = (new Email())
            ->subject($subject)
            ->from(new Address($this->mail, $this->mailName))
            ->to($user->getEmail())
            ->html($body)
            ->text($textBody);

        $this->mailer->send($msg);
    }

    public function sendNewPostMail(User $user, String $tokenName, String $postTitle, String $slug): void
    {
        $this->translator->setLocale($user->getLocale());

        $body = $this->twigEngine->render("mail/new_post.html.twig", [
            'username' => $user->getUsername(),
            'tokenName' => $tokenName,
            'postTitle' => $postTitle,
            'slug' => $slug,
        ]);

        $textBody = $this->twigEngine->render("mail/new_post.txt.twig", [
            'username' => $user->getUsername(),
            'tokenName' => $tokenName,
            'postTitle' => $postTitle,
            'slug' => $slug,
        ]);

        $subject = $this->translator->trans('email.new_post', ['%tokenName%' => $tokenName]);
        $msg = (new Email())
            ->subject($subject)
            ->from(new Address($this->mail, $this->mailName))
            ->to($user->getEmail())
            ->html($body)
            ->text($textBody);

        $this->mailer->send($msg);
    }

    public function sendGroupedPosts(User $user, String $tokenName, array $posts): void
    {
        $this->translator->setLocale($user->getLocale());

        $body = $this->twigEngine->render("mail/grouped_posts.html.twig", [
            'username' => $user->getUsername(),
            'tokenName' => $tokenName,
            'posts' => $posts,
        ]);

        $textBody = $this->twigEngine->render("mail/grouped_posts.txt.twig", [
            'username' => $user,
            'tokenName' => $tokenName,
            'posts' => $posts,
        ]);

        $subject = $this->translator->trans('email.grouped_posts', [
            '%number%' => count($posts),
            '%tokenName%' => $tokenName,
        ]);

        $msg = (new Email())
            ->subject($subject)
            ->from(new Address($this->mail, $this->mailName))
            ->to($user->getEmail())
            ->html($body)
            ->text($textBody);

        $this->mailer->send($msg);
    }

    public function sendNotListedTokenInfoMail(User $user, String $tokenName): void
    {
        $this->translator->setLocale($user->getLocale());

        $body = $this->twigEngine->render("mail/not_listed_token.html.twig", [
            'tokenName' => $tokenName,
        ]);

        $textBody = $this->twigEngine->render("mail/not_listed_token.txt.twig", [
            'tokenName' => $tokenName,
        ]);

        $subject = $this->translator->trans('email.no_deployed_token_subject');

        $msg = (new Email())
            ->subject($subject)
            ->from(new Address($this->mail, $this->mailName))
            ->to($user->getEmail())
            ->html($body)
            ->text($textBody);

        $this->mailer->send($msg);
    }

    public function sendTokenRemovedFromTradingInfoMail(User $user, String $tokenName): void
    {
        $this->translator->setLocale($user->getLocale());

        $body = $this->twigEngine->render("mail/token_removed_from_trading.html.twig", [
            'tokenName' => $tokenName,
        ]);

        $textBody = $this->twigEngine->render("mail/token_removed_from_trading.txt.twig", [
            'tokenName' => $tokenName,
        ]);

        $subject = $this->translator->trans('email.removed_from_trading.subject');

        $msg = (new Email())
            ->subject($subject)
            ->from(new Address($this->mail, $this->mailName))
            ->to($user->getEmail())
            ->html($body)
            ->text($textBody);

        $this->mailer->send($msg);
    }

    public function sendTokenDeployedMail(User $user, String $tokenName): void
    {
        $this->translator->setLocale($user->getLocale());

        $body = $this->twigEngine->render("mail/new_token_deployed.html.twig", [
            'nickname' => $user->getNickname(),
            'tokenName' => $tokenName,
        ]);

        $textBody = $this->twigEngine->render("mail/new_token_deployed.txt.twig", [
            'nickname' => $user->getNickname(),
            'tokenName' => $tokenName,
        ]);

        $subject = $this->translator->trans('email.new_token_deployed');
        $msg = (new Email())
            ->subject($subject)
            ->from(new Address($this->mail, $this->mailName))
            ->to($user->getEmail())
            ->html($body)
            ->text($textBody);

        $this->mailer->send($msg);
    }

    public function sendNoOrdersMail(User $user, String $tokenName): void
    {
        $this->translator->setLocale($user->getLocale());

        $body = $this->twigEngine->render("mail/no_orders.html.twig", [
            'nickname' => $user->getNickname(),
            'tokenName' => $tokenName,
        ]);

        $textBody = $this->twigEngine->render("mail/no_orders.txt.twig", [
            'nickname' => $user->getNickname(),
            'tokenName' => $tokenName,
        ]);

        $subject = $this->translator->trans('email.orders');
        $msg = (new Email())
            ->subject($subject)
            ->from(new Address($this->mail, $this->mailName))
            ->to($user->getEmail())
            ->html($body)
            ->text($textBody);

        $this->mailer->send($msg);
    }

    public function sendKnowledgeBaseMail(User $user, Token $token): void
    {
        $this->translator->setLocale($user->getLocale());

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
            ['url' => 'How-to-deploy-a-token-to-the-blockchain'],
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
        $msg = (new Email())
            ->subject($subject)
            ->from(new Address($this->mail, $this->mailName))
            ->to($user->getEmail())
            ->html($body)
            ->text($textBody);

        $this->mailer->send($msg);
    }

    public function sendTokenMarketingTipMail(User $user, String $kbLink): void
    {
        $this->translator->setLocale($user->getLocale());

        $pieces = explode('-', $kbLink);
        $title = implode(' ', $pieces);

        $url = $this->urlGenerator->generate(
            'kb_show',
            ['url' => $kbLink],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $body = $this->twigEngine->render("mail/token_marketing_tips.html.twig", [
            'nickname' => $user->getNickname(),
            'url' => $url,
            'title' => $title,
        ]);

        $textBody = $this->twigEngine->render("mail/token_marketing_tips.txt.twig", [
            'nickname' => $user->getNickname(),
            'url' => $url,
            'title' => $title,
        ]);
        $subject = $this->translator->trans('userNotification.type.token_marketing_tips');
        $msg = (new Email())
            ->subject($subject)
            ->from(new Address($this->mail, $this->mailName))
            ->to($user->getEmail())
            ->html($body)
            ->text($textBody);

        $this->mailer->send($msg);
    }

    public function sendTokenPromotionMail(Token $token): void
    {
        /** @var User $user */
        $user = $token->getOwner();
        $this->translator->setLocale($user->getLocale());

        $url = $this->urlGenerator->generate(
            'token_settings',
            [
                'tokenName' => $token->getName(),
                'tab' => 'promotion',
                'sub' => 'token_promotion',
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $body = $this->twigEngine->render("mail/token_promotion.html.twig", [
            'nickname' => $user->getNickname(),
            'url' => $url,
        ]);

        $textBody = $this->twigEngine->render("mail/token_promotion.txt.twig", [
            'nickname' => $user->getNickname(),
            'url' => $url,
        ]);

        $subject = $this->translator->trans('userNotification.type.token_promotion');
        $msg = (new Email())
            ->subject($subject)
            ->from(new Address($this->mail, $this->mailName))
            ->to($user->getEmail())
            ->html($body)
            ->text($textBody);

        $this->mailer->send($msg);
    }

    public function sendAirdropFeatureMail(Token $token): void
    {
        /** @var User $user */
        $user = $token->getOwner();
        $this->translator->setLocale($user->getLocale());

        $modalUrl = $this->urlGenerator->generate(
            'token_settings',
            [
                'tokenName' => $token->getName(),
                'tab' => 'promotion',
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
        $msg = (new Email())
            ->subject($subject)
            ->from(new Address($this->mail, $this->mailName))
            ->to($user->getEmail())
            ->html($body)
            ->text($textBody);

        $this->mailer->send($msg);
    }

    public function sendMintmeHostMail(User $user, string $price, string $freeDays, string $mintmeHostPath): void
    {
        $this->translator->setLocale($user->getLocale());

        $body = $this->twigEngine->render("mail/mintme_host.html.twig", [
            'nickname' => $user->getNickname(),
            'freeDays' => $freeDays,
            'price' => $price,
            'mintmeHostPath' => $mintmeHostPath,
        ]);

        $textBody = $this->twigEngine->render("mail/mintme_host.txt.twig", [
            'nickname' => $user->getNickname(),
            'freeDays' => $freeDays,
            'price' => $price,
            'mintmeHostPath' => $mintmeHostPath,
        ]);

        $subject = $this->translator->trans('mail.mintme_host_subject');
        $msg = (new Email())
            ->subject($subject)
            ->from(new Address($this->mail, $this->mailName))
            ->to($user->getEmail())
            ->html($body)
            ->text($textBody);

        $this->mailer->send($msg);
    }

    public function sendAirdropClaimedMail(
        User $user,
        Token $token,
        Money $airdropReward,
        string $airdropReferralCode
    ): void {
        $this->translator->setLocale($user->getLocale());

        $tokenPostsLink = $this->urlGenerator->generate(
            'token_show_post',
            [
                'name' => $token->getName(),
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $AirdropReferralLink = $this->urlGenerator->generate(
            'airdrop_referral',
            [
                'tokenName' => $token->getName(),
                'hash' => $airdropReferralCode,
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $body = $this->twigEngine->render("mail/airdrop_claimed.html.twig", [
            'nickname' => $user->getNickname(),
            'airdropReward' => $this->moneyWrapper->format($airdropReward),
            'tokenName' => $token->getName(),
            'tokenPostsLink' => $tokenPostsLink,
            'airdropReferralLink' => $AirdropReferralLink,
            'tokenSubunit' => Token::TOKEN_SUBUNIT,
        ]);

        $textBody = $this->twigEngine->render("mail/airdrop_claimed.txt.twig", [
            'nickname' => $user->getNickname(),
            'airdropReward' => $this->moneyWrapper->format($airdropReward),
            'tokenName' => $token->getName(),
            'tokenPostsLink' => $tokenPostsLink,
            'airdropReferralLink' => $AirdropReferralLink,
            'tokenSubunit' => Token::TOKEN_SUBUNIT,
        ]);

        $subject = $this->translator->trans('mail.airdrop_claimed.subject');
        $msg = (new Email())
            ->subject($subject)
            ->from(new Address($this->mail, $this->mailName))
            ->to($user->getEmail())
            ->html($body)
            ->text($textBody);

        $this->mailer->send($msg);
    }

    public function sentMintmeExchangeMail(User $user, array $exchangeCryptos, string $cryptosList): void
    {
        $this->translator->setLocale($user->getLocale());

        $body = $this->twigEngine->render("mail/exchange_mintme.html.twig", [
            'nickname' => $user->getNickname(),
            'exchangeCryptos' => $exchangeCryptos,
            'cryptosList' => $cryptosList,
            'mintmeSymbol' => Symbols::MINTME,
        ]);

        $textBody = $this->twigEngine->render("mail/exchange_mintme.txt.twig", [
            'nickname' => $user->getNickname(),
            'exchangeCryptos' => $exchangeCryptos,
            'cryptosList' => $cryptosList,
            'mintmeSymbol' => Symbols::MINTME,
        ]);

        $subject = $this->translator->trans('mail.can_exchange.header');
        $msg = (new Email())
            ->subject($subject)
            ->from(new Address($this->mail, $this->mailName))
            ->to($user->getEmail())
            ->html($body)
            ->text($textBody);

        $this->mailer->send($msg);
    }

    public function sendOwnTokenDeployedMail(Token $token, TokenDeploy $tokenDeploy): void
    {
        $user = $token->getProfile()->getUser();
        $this->translator->setLocale($user->getLocale());

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
        $explorerUrl = $this->explorerUrlConfig->getExplorerUrl(
            $tokenDeploy->getCrypto()->getSymbol(),
            $tokenDeploy->getTxHash()
        );

        $body = $this->twigEngine->render("mail/token_deployed.html.twig", [
            'nickname' => $user->getNickname(),
            'tokenName' => $token->getName(),
            'explorerUrl' => $explorerUrl,
            'tokenSalesUrl' => $tokenSalesUrl,
            'aimingUrl' => $aimingUrl,
            'ideasUrl' => $ideasUrl,
            'stuckUrl' => $stuckUrl,
            'talkingUrl' => $talkingUrl,
        ]);

        $textBody = $this->twigEngine->render("mail/token_deployed.txt.twig", [
            'nickname' => $user->getNickname(),
            'tokenName' => $token->getName(),
            'explorerUrl' => $explorerUrl,
            'tokenSalesUrl' => $tokenSalesUrl,
            'aimingUrl' => $aimingUrl,
            'ideasUrl' => $ideasUrl,
            'stuckUrl' => $stuckUrl,
            'talkingUrl' => $talkingUrl,
        ]);

        $subject = $this->translator->trans('mail.token_deployed.subject');
        $msg = (new Email())
            ->subject($subject)
            ->from(new Address($this->mail, $this->mailName))
            ->to($user->getEmail())
            ->html($body)
            ->text($textBody);

        $this->mailer->send($msg);
    }

    public function sendRewardNewParticipantMail(
        User $user,
        string $rewardToken,
        string $rewardTitle,
        string $slug
    ): void {
        $this->translator->setLocale($user->getLocale());

        $body = $this->twigEngine->render('mail/new_reward_participant.html.twig', [
            'nickname' => $user->getNickname(),
            'rewardToken' => $rewardToken,
            'rewardTitle' => $rewardTitle,
            'slug' => $slug,
        ]);

        $textBody = $this->twigEngine->render('mail/new_reward_participant.txt.twig', [
            'nickname' => $user->getNickname(),
            'rewardToken' => $rewardToken,
            'rewardTitle' => $rewardTitle,
            'slug' => $slug,
        ]);

        $subject = $this->translator->trans('mail.rewards_bounties.notification.new_reward_participant.subject');

        $msg = (new Email())
            ->subject($subject)
            ->from(new Address($this->mail, $this->mailName))
            ->to($user->getEmail())
            ->html($body)
            ->text($textBody);

        $this->mailer->send($msg);
    }

    public function sendRewardVolunteerAcceptedMail(
        User $user,
        string $rewardToken,
        string $rewardTitle,
        string $slug
    ): void {
        $this->translator->setLocale($user->getLocale());

        $body = $this->twigEngine->render('mail/reward_volunteer_accepted.html.twig', [
            'nickname' => $user->getNickname(),
            'rewardToken' => $rewardToken,
            'rewardTitle' => $rewardTitle,
            'slug' => $slug,
        ]);

        $textBody = $this->twigEngine->render('mail/reward_volunteer_accepted.txt.twig', [
            'nickname' => $user->getNickname(),
            'rewardToken' => $rewardToken,
            'rewardTitle' => $rewardTitle,
            'slug' => $slug,
        ]);

        $subject = $this->translator->trans('mail.rewards_bounties.notification.new_reward_participant.subject');

        $msg = (new Email())
            ->subject($subject)
            ->from(new Address($this->mail, $this->mailName))
            ->to($user->getEmail())
            ->html($body)
            ->text($textBody);

        $this->mailer->send($msg);
    }

    public function sendRewardVolunteerRejectedMail(
        User $user,
        string $rewardToken,
        string $rewardTitle,
        string $slug
    ): void {
        $this->translator->setLocale($user->getLocale());

        $body = $this->twigEngine->render('mail/reward_volunteer_rejected.html.twig', [
            'nickname' => $user->getNickname(),
            'rewardToken' => $rewardToken,
            'rewardTitle' => $rewardTitle,
            'slug' => $slug,
        ]);

        $textBody = $this->twigEngine->render('mail/reward_volunteer_rejected.txt.twig', [
            'nickname' => $user->getNickname(),
            'rewardToken' => $rewardToken,
            'rewardTitle' => $rewardTitle,
            'slug' => $slug,
        ]);

        $subject = $this->translator->trans('mail.rewards_bounties.notification.new_reward_participant.subject');

        $msg = (new Email())
            ->subject($subject)
            ->from(new Address($this->mail, $this->mailName))
            ->to($user->getEmail())
            ->html($body)
            ->text($textBody);

        $this->mailer->send($msg);
    }

    public function sendRewardVolunteerCompletedMail(
        User $user,
        string $rewardToken,
        string $rewardTitle,
        string $slug
    ): void {
        $this->translator->setLocale($user->getLocale());

        $body = $this->twigEngine->render('mail/reward_volunteer_completed.html.twig', [
            'nickname' => $user->getNickname(),
            'rewardToken' => $rewardToken,
            'rewardTitle' => $rewardTitle,
            'slug' => $slug,
        ]);

        $textBody = $this->twigEngine->render('mail/reward_volunteer_completed.txt.twig', [
            'nickname' => $user->getNickname(),
            'rewardToken' => $rewardToken,
            'rewardTitle' => $rewardTitle,
            'slug' => $slug,
        ]);

        $subject = $this->translator->trans('mail.rewards_bounties.notification.new_reward_participant.subject');

        $msg = (new Email())
            ->subject($subject)
            ->from(new Address($this->mail, $this->mailName))
            ->to($user->getEmail())
            ->html($body)
            ->text($textBody);

        $this->mailer->send($msg);
    }

    public function sendRewardParticipantRejectedMail(
        User $user,
        string $rewardToken,
        string $rewardTitle,
        string $slug
    ): void {
        $this->translator->setLocale($user->getLocale());

        $body = $this->twigEngine->render('mail/reward_participant_rejected.html.twig', [
            'nickname' => $user->getNickname(),
            'rewardToken' => $rewardToken,
            'rewardTitle' => $rewardTitle,
            'slug' => $slug,
        ]);

        $textBody = $this->twigEngine->render('mail/reward_participant_rejected.txt.twig', [
            'nickname' => $user->getNickname(),
            'rewardToken' => $rewardToken,
            'rewardTitle' => $rewardTitle,
            'slug' => $slug,
        ]);

        $subject = $this->translator->trans('mail.rewards_bounties.notification.new_reward_participant.subject');

        $msg = (new Email())
            ->subject($subject)
            ->from(new Address($this->mail, $this->mailName))
            ->to($user->getEmail())
            ->html($body)
            ->text($textBody);

        $this->mailer->send($msg);
    }

    public function sendRewardParticipantDeliveredMail(
        User $user,
        string $rewardToken,
        string $rewardTitle,
        string $slug
    ): void {
        $this->translator->setLocale($user->getLocale());

        $body = $this->twigEngine->render('mail/reward_participant_delivered.html.twig', [
            'nickname' => $user->getNickname(),
            'rewardToken' => $rewardToken,
            'rewardTitle' => $rewardTitle,
            'slug' => $slug,
        ]);

        $textBody = $this->twigEngine->render('mail/reward_participant_delivered.txt.twig', [
            'nickname' => $user->getNickname(),
            'rewardToken' => $rewardToken,
            'rewardTitle' => $rewardTitle,
            'slug' => $slug,
        ]);

        $subject = $this->translator->trans('mail.rewards_bounties.notification.new_reward_participant.subject');

        $msg = (new Email())
            ->subject($subject)
            ->from(new Address($this->mail, $this->mailName))
            ->to($user->getEmail())
            ->html($body)
            ->text($textBody);

        $this->mailer->send($msg);
    }

    public function sendRewardParticipantRefundMail(
        User $user,
        string $ownerNickname,
        string $amount,
        string $rewardToken,
        string $rewardTitle,
        string $slug
    ): void {
        $this->translator->setLocale($user->getLocale());

        $body = $this->twigEngine->render('mail/reward_participant_refund.html.twig', [
            'nickname' => $user->getNickname(),
            'ownerProfileUrl' => $ownerNickname,
            'ownerNickname' => $ownerNickname,
            'amount' => $amount,
            'urlToken' => $rewardToken,
            'rewardToken' => $rewardToken,
            'urlProduct' => $rewardToken,
            'rewardTitle' => $rewardTitle,
            'slug' => $slug,
        ]);

        $textBody = $this->twigEngine->render('mail/reward_participant_refund.txt.twig', [
            'nickname' => $user->getNickname(),
            'ownerProfileUrl' => $ownerNickname,
            'ownerNickname' => $ownerNickname,
            'amount' => $amount,
            'urlToken' => $rewardToken,
            'rewardToken' => $rewardToken,
            'urlProduct' => $rewardToken,
            'rewardTitle' => $rewardTitle,
            'slug' => $slug,
        ]);

        $subject = $this->translator->trans('mail.rewards_bounties.notification.new_reward_participant.subject');

        $msg = (new Email())
            ->subject($subject)
            ->from(new Address($this->mail, $this->mailName))
            ->to($user->getEmail())
            ->html($body)
            ->text($textBody);

        $this->mailer->send($msg);
    }

    public function sendRewardNewVolunteerMail(User $user, string $rewardToken, string $rewardTitle, string $slug): void
    {
        $this->translator->setLocale($user->getLocale());

        $body = $this->twigEngine->render('mail/new_reward_volunteer.html.twig', [
            'nickname' => $user->getNickname(),
            'rewardToken' => $rewardToken,
            'rewardTitle' => $rewardTitle,
            'slug' => $slug,
        ]);

        $textBody = $this->twigEngine->render('mail/new_reward_volunteer.txt.twig', [
            'nickname' => $user->getNickname(),
            'rewardToken' => $rewardToken,
            'rewardTitle' => $rewardTitle,
            'slug' => $slug,
        ]);

        $subject = $this->translator->trans('mail.rewards_bounties.notification.new_reward_participant.subject');

        $msg = (new Email())
            ->subject($subject)
            ->from(new Address($this->mail, $this->mailName))
            ->to($user->getEmail())
            ->html($body)
            ->text($textBody);

        $this->mailer->send($msg);
    }

    public function sendRewardNewMail(
        User $user,
        string $rewardToken,
        string $rewardTitle,
        string $rewardType,
        string $slug
    ): void {
        $this->translator->setLocale($user->getLocale());

        $body = $this->twigEngine->render('mail/new_reward_created.html.twig', [
            'nickname' => $user->getNickname(),
            'rewardToken' => $rewardToken,
            'rewardTitle' => $rewardTitle,
            'rewardType' => $rewardType,
            'slug' => $slug,
        ]);

        $textBody = $this->twigEngine->render('mail/new_reward_created.txt.twig', [
            'nickname' => $user->getNickname(),
            'rewardToken' => $rewardToken,
            'rewardTitle' => $rewardTitle,
            'rewardType' => $rewardType,
            'slug' => $slug,
        ]);

        $subject = $this->translator->trans('mail.rewards_bounties.notification.new_reward_participant.subject');

        $msg = (new Email())
            ->subject($subject)
            ->from(new Address($this->mail, $this->mailName))
            ->to($user->getEmail())
            ->html($body)
            ->text($textBody);

        $this->mailer->send($msg);
    }

    public function sendMarketCreatedMail(User $user, string $tokenName, string $cryptoSymbol): void
    {
        $this->translator->setLocale($user->getLocale());

        $body = $this->twigEngine->render('mail/new_market_created.html.twig', [
            'nickname' => $user->getNickname(),
            'tokenName' => $tokenName,
            'cryptoSymbol' => $cryptoSymbol,
        ]);

        $textBody = $this->twigEngine->render('mail/new_market_created.txt.twig', [
            'nickname' => $user->getNickname(),
            'tokenName' => $tokenName,
            'cryptoSymbol' => $cryptoSymbol,
        ]);

        $subject = $this->translator->trans('mail.market_created.subject');

        $msg = (new Email())
            ->subject($subject)
            ->from(new Address($this->mail, $this->mailName))
            ->to($user->getEmail())
            ->html($body)
            ->text($textBody);

        $this->mailer->send($msg);
    }

    public function sendNewBuyOrderMail(User $owner, User $maker, string $tokenName, string $cryptoSymbol): void
    {
        $this->translator->setLocale($owner->getLocale());

        $profile = $maker->getProfile();
        $ownerEmail = $owner->getEmail();

        $body = $this->twigEngine->render('mail/new_buy_order.html.twig', [
            'nickname' => $owner->getNickname(),
            'profileNickname' => $profile->getNickname(),
            'tokenName' => $tokenName,
            'cryptoSymbol' => $cryptoSymbol,
        ]);

        $textBody = $this->twigEngine->render('mail/new_buy_order.txt.twig', [
            'nickname' => $owner->getNickname(),
            'profileNickname' => $profile->getNickname(),
            'tokenName' => $tokenName,
            'cryptoSymbol' => $cryptoSymbol,
        ]);

        $subject = $this->translator->trans('mail.new_buy_order.subject');

        $msg = (new Email())
            ->subject($subject)
            ->from(new Address($this->mail, $this->mailName))
            ->to($ownerEmail)
            ->html($body)
            ->text($textBody);

        $this->mailer->send($msg);
    }

    public function sendFailedLoginBlock(User $user): void
    {
        $this->translator->setLocale($user->getLocale());
        $ownerEmail = $user->getEmail();
        $subject = $this->translator->trans('mail.subject.login.user_blocked');

        $body = $this->twigEngine->render('mail/failed_login_block.html.twig', [
            'maxHours' => $this->failedLoginConfig->getMaxHours(),
            'ownerEmail' => $ownerEmail,
            'nickname' => $user->getNickname(),
        ]);

        $textBody = $this->twigEngine->render('mail/failed_login_block.txt.twig', [
            'maxHours' => $this->failedLoginConfig->getMaxHours(),
            'ownerEmail' => $ownerEmail,
            'nickname' => $user->getNickname(),
        ]);

        $msg = (new Email())
            ->subject($subject)
            ->from(new Address($this->mail, $this->mailName))
            ->to($ownerEmail)
            ->html($body)
            ->text($textBody);

        $this->mailer->send($msg);
    }

    public function sendVerificationCode(User $user, string $code, string $subject, ?string $to = null): void
    {
        $this->translator->setLocale($user->getLocale());
        $ownerEmail = $to ?? $user->getEmail();

        $body = $this->twigEngine->render('mail/verification_code.html.twig', [
            'nickName' => $user->getNickname(),
            'code' => $code,
        ]);

        $textBody = $this->twigEngine->render('mail/verification_code.txt.twig', [
            'nickName' => $user->getNickname(),
            'code' => $code,
        ]);

        $msg = (new Email())
            ->subject($subject)
            ->from(new Address($this->mail, $this->mailName))
            ->to($ownerEmail)
            ->html($body)
            ->text($textBody);

        $this->mailer->send($msg);
    }

    public function sendConfirmationEmailMessage(UserInterface $user): void
    {
        $template = $this->registrationTemplate;
        $url = $this->urlGenerator->generate(
            'fos_user_registration_confirm',
            [
                'token' => $user->getConfirmationToken(),
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $context = [
            'user' => $user,
            'confirmationUrl' => $url,
        ];

        $this->sendFosTemplateMessage($template, $context, $user->getEmail());
    }

    public function sendResettingEmailMessage(UserInterface $user): void
    {
        $template = $this->resettingTemplate;
        $url = $this->urlGenerator->generate(
            'fos_user_resetting_reset',
            [
                'token' => $user->getConfirmationToken(),
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $context = [
            'user' => $user,
            'confirmationUrl' => $url,
        ];

        $this->sendFosTemplateMessage($template, $context, $user->getEmail());
    }

    protected function sendFosTemplateMessage(string $templateName, array $context, string $toEmail): void
    {
        $template = $this->environment->load($templateName);
        $subject = $template->renderBlock('subject', $context);
        $textBody = $template->renderBlock('body_text', $context);

        $htmlBody = '';

        if ($template->hasBlock('body_html', $context)) {
            $htmlBody = $template->renderBlock('body_html', $context);
        }

        $message = (new Email())
            ->subject($subject)
            ->from(new Address($this->mail, $this->mailName))
            ->to($toEmail)
            ->html($htmlBody)
            ->text($textBody);


        $this->mailer->send($message);
    }
}
