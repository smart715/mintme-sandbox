<?php declare(strict_types = 1);

namespace App\Tests\Mailer;

use App\Config\FailedLoginConfig;
use App\Entity\Crypto;
use App\Entity\PendingWithdrawInterface;
use App\Entity\Profile;
use App\Entity\Token\Token;
use App\Entity\Token\TokenDeploy;
use App\Entity\TradableInterface;
use App\Entity\User;
use App\Entity\UserLoginInfo;
use App\Exchange\Config\DeployCostConfig;
use App\Mailer\Mailer;
use App\Manager\CryptoManagerInterface;
use App\Services\TranslatorService\TranslatorInterface;
use App\SmartContract\Config\ExplorerUrlsConfigInterface;
use App\Wallet\Model\Address;
use App\Wallet\Model\Amount;
use App\Wallet\Model\LackMainBalanceReport;
use App\Wallet\Money\MoneyWrapperInterface;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Templating\EngineInterface;
use Twig\Environment;

class MailerTest extends TestCase
{
    private const SUBJECT = 'test_subject';
    private const BODY = 'test_body';
    private const MAIL = 'test@test.test';
    private const USER_MAIL = 'email@test.test';
    private const REGISTRATION_TEMPLATE = 'bundles/FOSUserBundle/Registration/email.html.twig';
    private const RESETTING_TEMPLATE = 'bundles/FOSUserBundle/Resetting/email.html.twig';
    private const TWIG_INVOKE_COUNT = 2;
    private const LOCALE = 'en';

    private const MAIL_NAME = 'MintMe';

    public function testSendLackBalanceReportMail(): void
    {
        $mailer = $this->createMailer(0, 0, 0, 0, 0, 0, 'Lack of balance in gateway wallet');

        $mailer->sendLackBalanceReportMail(
            self::USER_MAIL,
            $this->mockLackMainBalanceReport()
        );
    }

    public function testSendTransactionDelayedMail(): void
    {
        $mailer = $this->createMailer(1, 1);

        $mailer->sendTransactionDelayedMail($this->mockUser());
    }

    public function testSendWithdrawConfirmationMail(): void
    {
        $mailer = $this->createMailer(1, 1, 1);

        $mailer->sendWithdrawConfirmationMail(
            $this->mockUser(),
            $this->mockPendingWithdraw(),
            $this->mockTradable(),
            'test'
        );
    }

    public function testSendAuthCode(): void
    {
        $mailer = $this->createMailer(0, 2, 0, 0, 0);

        $mailer->sendAuthCode($this->mockTwoFactorInterface());
    }

    public function testSendAuthCodeToMail(): void
    {
        $mailer = $this->createMailer(0, 0, 0, 0, 0);

        $mailer->sendAuthCodeToMail(self::SUBJECT, 'test', $this->mockTwoFactorInterface());
    }

    public function testSendTransactionCompletedMail(): void
    {
        $mailer = $this->createMailer(1, 1, 1);

        $mailer->sendTransactionCompletedMail(
            $this->mockUser(),
            $this->mockTradable(),
            $this->dummyMoneyObject(),
            'test',
            'test',
            'test'
        );
    }

    public function testSendPasswordResetMailWithResettingFalse(): void
    {
        $mailer = $this->createMailer(0, 1);

        $mailer->sendPasswordResetMail($this->mockUser(), false);
    }

    public function testSendPasswordResetMailWithResettingTrue(): void
    {
        $mailer = $this->createMailer(0, 1);

        $mailer->sendPasswordResetMail($this->mockUser(), true);
    }

    public function testSendTokenDeletedMail(): void
    {
        $mailer = $this->createMailer(0, 1);

        $mailer->sendTokenDeletedMail($this->mockToken());
    }

    public function testSendNewDeviceDetectedMail(): void
    {
        $mailer = $this->createMailer(0, 2);

        $mailer->sendNewDeviceDetectedMail($this->mockUser(), $this->mockUserLoginInfo());
    }

    public function testSendProfileFillingReminderMail(): void
    {
        $mailer = $this->createMailer(0, 1);

        $mailer->sendProfileFillingReminderMail($this->mockUser());
    }

    public function testSendTokenDescriptionReminderMail(): void
    {
        $mailer = $this->createMailer(0, 1);

        $mailer->sendTokenDescriptionReminderMail($this->mockToken(0, 4));
    }

    public function testSendNewInvestorMail(): void
    {
        $mailer = $this->createMailer(0, 1);

        $mailer->sendNewInvestorMail($this->mockToken(0, 4), 'TEST', 'MINTME');
    }

    public function testSendNewPostMail(): void
    {
        $mailer = $this->createMailer(0, 1);

        $mailer->sendNewPostMail($this->mockUser(), 'TEST', 'TEST', 'TEST');
    }

    public function testSendGroupedPosts(): void
    {
        $mailer = $this->createMailer(0, 1);

        $mailer->sendGroupedPosts($this->mockUser(), 'TEST', []);
    }

    public function testSendGroupedRewards(): void
    {
        $mailer = $this->createMailer(0, 1);

        $mailer->sendGroupedRewardsMail($this->mockUser(), 'TEST', [], 'reward');
    }

    public function testSendGroupedBounties(): void
    {
        $mailer = $this->createMailer(0, 1);

        $mailer->sendGroupedRewardsMail($this->mockUser(), 'TEST', [], 'bounty');
    }

    public function testSendTokenDeployedMail(): void
    {
        $mailer = $this->createMailer(0, 1);

        $mailer->sendTokenDeployedMail($this->mockUser(), 'TEST');
    }

    public function testSendTokenRemovedFromTradingInfoMail(): void
    {
        $mailer = $this->createMailer(0, 1);

        $mailer->sendTokenRemovedFromTradingInfoMail($this->mockUser(), 'TEST');
    }

    public function testSendNotListedTokenInfoMail(): void
    {
        $mailer = $this->createMailer(0, 1);

        $mailer->sendNotListedTokenInfoMail($this->mockUser(), 'TEST');
    }

    public function testSendNoOrdersMail(): void
    {
        $mailer = $this->createMailer(0, 1);

        $mailer->sendNoOrdersMail($this->mockUser(), 'TEST');
    }

    public function testSendKnowledgeBaseMail(): void
    {
        $mailer = $this->createMailer(6, 1);

        $mailer->sendKnowledgeBaseMail($this->mockUser(), $this->mockToken(0, 0));
    }

    public function testSendTokenMarketingTipMail(): void
    {
        $mailer = $this->createMailer(1, 1);

        $mailer->sendTokenMarketingTipMail($this->mockUser(), 'TEST');
    }

    public function testSendAirdropFeatureMail(): void
    {
        $mailer = $this->createMailer(1, 1);

        $mailer->sendAirdropFeatureMail($this->mockToken(2, 1, 1));
    }

    public function testSendMintmeHostMail(): void
    {
        $mailer = $this->createMailer(0, 1);

        $mailer->sendMintmeHostMail($this->mockUser(), 'TEST', 'TEST', 'TEST');
    }

    public function testSendAirdropClaimedMail(): void
    {
        $mailer = $this->createMailer(2, 1, 2);

        $mailer->sendAirdropClaimedMail(
            $this->mockUser(),
            $this->mockToken(0, 0, 4),
            $this->dummyMoneyObject(),
            'TEST'
        );
    }

    public function testSentMintmeExchangeMail(): void
    {
        $mailer = $this->createMailer(0, 1);

        $mailer->sentMintmeExchangeMail($this->mockUser(), [], 'TEST');
    }

    public function testSendOwnTokenDeployedMail(): void
    {
        $mailer = $this->createMailer(5, 1, 0, 0, 1, 1);

        $mailer->sendOwnTokenDeployedMail($this->mockToken(1, 0), $this->mockTokenDeploy());
    }

    public function testSendRewardNewParticipantMail(): void
    {
        $mailer = $this->createMailer(0, 1);

        $mailer->sendRewardNewParticipantMail($this->mockUser(), 'TEST', 'TEST', 'TEST');
    }

    public function testSendRewardVolunteerAcceptedMail(): void
    {
        $mailer = $this->createMailer(0, 1);

        $mailer->sendRewardVolunteerAcceptedMail($this->mockUser(), 'TEST', 'TEST', 'TEST');
    }

    public function testSendRewardVolunteerCompletedMail(): void
    {
        $mailer = $this->createMailer(0, 1);

        $mailer->sendRewardVolunteerCompletedMail($this->mockUser(), 'TEST', 'TEST', 'TEST');
    }

    public function testSendRewardNewVolunteerMail(): void
    {
        $mailer = $this->createMailer(0, 1);

        $mailer->sendRewardNewVolunteerMail($this->mockUser(), 'TEST', 'TEST', 'TEST');
    }

    public function testSendRewardNewMail(): void
    {
        $mailer = $this->createMailer(0, 1);

        $mailer->sendRewardNewMail($this->mockUser(), 'TEST', 'TEST', 'TEST', 'TEST');
    }

    public function testSendMarketCreatedMail(): void
    {
        $mailer = $this->createMailer(0, 1);

        $mailer->sendMarketCreatedMail($this->mockUser(), 'TEST', 'TEST');
    }

    public function testSendNewBuyOrderMail(): void
    {
        $mailer = $this->createMailer(0, 1);

        $mailer->sendNewBuyOrderMail($this->mockUser(), $this->mockUser(), 'TEST', 'TEST');
    }

    public function testSendFailedLoginBlock(): void
    {
        $mailer = $this->createMailer(0, 1, 0, 2);

        $mailer->sendFailedLoginBlock($this->mockUser());
    }

    public function testSendPhoneVerificationCode(): void
    {
        $mailer = $this->createMailer();

        $mailer->sendVerificationCode($this->mockUser(), 'TEST', self::SUBJECT);
    }

    public function testSendRewardVolunteerRejectedMail(): void
    {
        $mailer = $this->createMailer(0, 1);

        $mailer->sendRewardVolunteerRejectedMail($this->mockUser(), 'TEST', 'TEST', 'TEST');
    }

    public function testSendRewardParticipantRejectedMail(): void
    {
        $mailer = $this->createMailer(0, 1);

        $mailer->sendRewardParticipantRejectedMail($this->mockUser(), 'TEST', 'TEST', 'TEST');
    }

    public function testSendRewardParticipantDeliveredMail(): void
    {
        $mailer = $this->createMailer(0, 1);

        $mailer->sendRewardParticipantDeliveredMail($this->mockUser(), 'TEST', 'TEST', 'TEST');
    }

    public function testSendRewardParticipantRefundMail(): void
    {
        $mailer = $this->createMailer(0, 1);

        $mailer->sendRewardParticipantRefundMail($this->mockUser(), 'TEST', 'TEST', 'TEST', 'TEST', 'TEST');
    }

    private function createMailer(
        int $urlGeneratorGenerateInvokeCount = 0,
        int $translatorTransInvokeCount = 0,
        int $moneyWrapperFormatInvoke = 0,
        int $getMaxHoursInvokeCount = 0,
        int $translatorSetLocaleInvokeCount = 1,
        int $getExplorerUrlInvokeCount = 0,
        string $subject = self::SUBJECT
    ): Mailer {
        return new Mailer(
            self::MAIL,
            self::MAIL_NAME,
            self::REGISTRATION_TEMPLATE,
            self::RESETTING_TEMPLATE,
            $this->mockMailer($subject),
            $this->mockEngine(self::TWIG_INVOKE_COUNT),
            $this->mockUrlGenerator($urlGeneratorGenerateInvokeCount),
            $this->mockTranslator($translatorTransInvokeCount, $translatorSetLocaleInvokeCount),
            $this->mockMoneyWrapper($moneyWrapperFormatInvoke),
            $this->mockExplorerUrlConfig($getExplorerUrlInvokeCount),
            $this->mockFailedLoginConfig($getMaxHoursInvokeCount),
            $this->createMock(Environment::class),
            $this->createMock(DeployCostConfig::class)
        );
    }

    private function mockMailer(string $subject): MailerInterface
    {
        $mailer = $this->createMock(MailerInterface::class);

        $mailer
            ->expects($this->once())
            ->method('send')
            ->willReturnCallback(function (Email $email) use ($subject): void {
                $user_mail = $email->getTo()[0]->getAddress();
                $from_mail = $email->getFrom()[0]->getAddress();
                $from_name = $email->getFrom()[0]->getName();

                $this->assertSame($subject, $email->getSubject());
                $this->assertStringContainsString(self::BODY, $email->getBody()->bodyToString());
                $this->assertCount(1, $email->getTo());
                $this->assertSame(self::USER_MAIL, $user_mail);
                $this->assertSame(self::MAIL, $from_mail);
                $this->assertSame(self::MAIL_NAME, $from_name);
            });

        return $mailer;
    }

    private function mockEngine(int $invokedCount = 0): EngineInterface
    {
        $engine = $this->createMock(EngineInterface::class);

        $engine->expects($this->exactly($invokedCount))
            ->method('render')
            ->willReturn(self::BODY);

        return $engine;
    }

    private function mockUrlGenerator(int $invokedCount = 0): UrlGeneratorInterface
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);

        $urlGenerator->expects($this->exactly($invokedCount))
            ->method('generate')
            ->willReturn("test");

        return $urlGenerator;
    }

    private function mockTranslator(int $invokedCount = 0, int $translatorSetLocaleInvokeCount = 0): TranslatorInterface
    {
        $translator = $this->createMock(TranslatorInterface::class);

        $translator->expects($this->exactly($invokedCount))
            ->method('trans')
            ->willReturn(self::SUBJECT);

        $translator->expects($this->exactly($translatorSetLocaleInvokeCount))
            ->method('setLocale')
            ->with(self::LOCALE);

        return $translator;
    }

    private function mockMoneyWrapper(int $formatInvokeCount = 0): MoneyWrapperInterface
    {
        $moneyWrapper = $this->createMock(MoneyWrapperInterface::class);

        if ($formatInvokeCount > 0) {
            $moneyWrapper->expects($this->exactly($formatInvokeCount))
                ->method('format')
            ->willReturnCallback(function (Money $money): string {
                return $money->getAmount();
            });
        }

        return $moneyWrapper;
    }

    private function mockExplorerUrlConfig(int $getExploredUrlCountInvoke = 0): ExplorerUrlsConfigInterface
    {
        $explorerUrlConfig = $this->createMock(ExplorerUrlsConfigInterface::class);

        if ($getExploredUrlCountInvoke > 0) {
            $explorerUrlConfig->expects($this->exactly($getExploredUrlCountInvoke))
                ->method('getExplorerUrl')
                ->willReturn('TEST');
        }

        return $explorerUrlConfig;
    }

    private function mockFailedLoginConfig(int $getMaxHourInvokeCounter = 0): FailedLoginConfig
    {
        $failedLoginConfig = $this->createMock(FailedLoginConfig::class);

        if ($getMaxHourInvokeCounter > 0) {
            $failedLoginConfig->expects($this->exactly($getMaxHourInvokeCounter))
                ->method('getMaxHours')
                ->willReturn(1);
        }

        return $failedLoginConfig;
    }

    private function mockUser(): User
    {
        $user = $this->createMock(User::class);
        $user->method('getEmail')->willReturn(self::USER_MAIL);
        $user->method('getUserName')->willReturn('test');
        $user->method('getLocale')->willReturn(self::LOCALE);

        return $user;
    }

    private function mockPendingWithdraw(): PendingWithdrawInterface
    {
        $pendingWithdraw = $this->createMock(PendingWithdrawInterface::class);
        $pendingWithdraw->expects($this->once())->method('getAmount')->willReturn($this->mockAmount());
        $pendingWithdraw->expects($this->once())->method('getHash')->willReturn('TEST');
        $pendingWithdraw->expects($this->once())->method('getAddress')->willReturn($this->mockAddress());

        return $pendingWithdraw;
    }

    private function mockTradable(int $getTradableInterface = 2): TradableInterface
    {
        $tradable = $this->createMock(TradableInterface::class);
        $tradable->expects($this->exactly($getTradableInterface))->method('getSymbol')->willReturn('WEB');

        return $tradable;
    }

    private function mockAmount(): Amount
    {
        $amount = $this->createMock(Amount::class);
        $amount->expects($this->once())->method('getAmount')->willReturn($this->dummyMoneyObject());

        return $amount;
    }

    private function dummyMoneyObject(string $amount = '0', string $currency = 'TOK'): Money
    {
        return new Money($amount, new Currency($currency));
    }

    private function mockAddress(): Address
    {
        $address = $this->createMock(Address::class);
        $address->expects($this->once())->method('getAddress')->willReturn('TEST');

        return $address;
    }

    private function mockTwoFactorInterface(): TwoFactorInterface
    {
        $twoFactorInterface = $this->createMock(TwoFactorInterface::class);

        $twoFactorInterface->expects($this->exactly(3))
            ->method('getEmailAuthRecipient')
            ->willReturn(self::USER_MAIL);

        $twoFactorInterface->expects($this->exactly(2))->method('getEmailAuthCode');

        return $twoFactorInterface;
    }

    private function mockToken(
        int $getProfileInvokeCount = 1,
        int $getOwnerInvokeCount = 1,
        int $getNameInvokeCount = 2
    ): Token {
        $token = $this->createMock(Token::class);
        $token->expects($this->exactly($getProfileInvokeCount))->method('getProfile')->willReturn($this->mockProfile());
        $token->expects($this->exactly($getOwnerInvokeCount))->method('getOwner')->willReturn($this->mockUser());
        $token->expects($this->exactly($getNameInvokeCount))->method('getName')->willReturn('TEST');

        return $token;
    }

    private function mockProfile(): Profile
    {
        $profile = $this->createMock(Profile::class);
        $profile->method('getUser')->willReturn($this->mockUser());

        return $profile;
    }

    private function mockUserLoginInfo(): UserLoginInfo
    {
        return $this->createMock(UserLoginInfo::class);
    }

    private function mockTokenDeploy(): TokenDeploy
    {
        $tokenDeploy = $this->createMock(TokenDeploy::class);
        $tokenDeploy->expects($this->once())->method('getCrypto')->willReturn($this->mockCrypto());
        $tokenDeploy->expects($this->once())->method('getTxHash')->willReturn('TEST');

        return $tokenDeploy;
    }

    private function mockCrypto(): Crypto
    {
        $crypto = $this->createMock(Crypto::class);
        $crypto->expects($this->once())->method('getSymbol')->willReturn('TOK');

        return $crypto;
    }

    private function mockLackMainBalanceReport(): LackMainBalanceReport
    {
        $lackMainBalanceReport = $this->createMock(LackMainBalanceReport::class);
        $lackMainBalanceReport->method('getAmount')->willReturn($this->dummyMoneyObject());
        $lackMainBalanceReport->method('getTradableBalance')->willReturn($this->dummyMoneyObject());
        $lackMainBalanceReport->method('getTradableAmount')->willReturn($this->dummyMoneyObject());
        $lackMainBalanceReport->method('getNetworkAmount')->willReturn($this->dummyMoneyObject());
        $lackMainBalanceReport->method('getNetworkBalance')->willReturn($this->dummyMoneyObject());

        return $lackMainBalanceReport;
    }
}
