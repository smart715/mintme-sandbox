<?php declare(strict_types = 1);

namespace App\Mailer;

use App\Entity\Crypto;
use App\Entity\DeployNotification;
use App\Entity\PendingWithdrawInterface;
use App\Entity\Token\Token;
use App\Entity\Token\TokenDeploy;
use App\Entity\TradableInterface;
use App\Entity\User;
use App\Entity\UserLoginInfo;
use App\Manager\CryptoManagerInterface;
use App\Wallet\Model\LackMainBalanceReport;
use Money\Money;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;

interface MailerInterface
{
    public function sendDeployNotificationMail(DeployNotification $deployNotification): void;

    public function sendLackBalanceReportMail(
        string $mail,
        LackMainBalanceReport $report
    ): void;

    public function sendTransactionDelayedMail(User $user): void;
    public function sendWithdrawConfirmationMail(
        User $user,
        PendingWithdrawInterface $withdrawData,
        TradableInterface $tradable,
        string $cryptoNetworkName
    ): void;
    public function sendAuthCodeToMail(string $subject, string $label, TwoFactorInterface $user): void;
    public function sendTransactionCompletedMail(
        User $user,
        TradableInterface $tradable,
        Money $amount,
        string $address,
        string $transactionType,
        string $cryptoNetworkName
    ): void;
    public function sendTokenDeletedMail(Token $token): void;
    public function sendPasswordResetMail(User $user, bool $resetting): void;
    public function sendNewDeviceDetectedMail(User $user, UserLoginInfo $userLoginInfo): void;
    public function sendProfileFillingReminderMail(User $user): void;
    public function sendTokenDescriptionReminderMail(Token $token): void;
    public function sendNewInvestorMail(Token $token, string $newInvestor, ?string $marketSymbol): void;
    public function sendNewPostMail(User $user, String $tokenName, String $postTitle, String $slug): void;
    public function sendTokenDeployedMail(User $user, String $tokenName): void;
    public function sendNoOrdersMail(User $user, String $tokenName): void;
    public function sendKnowledgeBaseMail(User $user, Token $token): void;
    public function sendTokenMarketingTipMail(User $user, string $kbLink): void;
    public function sendTokenPromotionMail(Token $token): void;
    public function sendAirdropFeatureMail(Token $token): void;
    public function sendMintmeHostMail(User $user, string $price, string $freeDays, string $mintmeHostPath): void;
    public function sendOwnTokenDeployedMail(Token $token, TokenDeploy $tokenDeploy): void;
    public function sentMintmeExchangeMail(User $user, array $exchangeCryptos, string $cryptoList): void;
    public function sendAirdropClaimedMail(
        User $user,
        Token $token,
        Money $airdropReward,
        string $airdropReferralCode
    ): void;
    public function sendGroupedRewardsMail(User $user, String $tokenName, array $rewards, string $type): void;
    public function sendGroupedPosts(User $user, String $tokenName, array $posts): void;
    public function sendNotListedTokenInfoMail(User $user, String $tokenName): void;
    public function sendTokenRemovedFromTradingInfoMail(User $user, String $tokenName): void;
    public function sendRewardVolunteerAcceptedMail(
        User $user,
        string $rewardToken,
        string $rewardTitle,
        string $slug
    ): void;
    public function sendRewardVolunteerRejectedMail(
        User $user,
        string $rewardToken,
        string $rewardTitle,
        string $slug
    ): void;
    public function sendRewardVolunteerCompletedMail(
        User $user,
        string $rewardToken,
        string $rewardTitle,
        string $slug
    ): void;
    public function sendRewardParticipantRejectedMail(
        User $user,
        string $rewardToken,
        string $rewardTitle,
        string $slug
    ): void;
    public function sendRewardParticipantDeliveredMail(
        User $user,
        string $rewardToken,
        string $rewardTitle,
        string $slug
    ): void;
    public function sendRewardParticipantRefundMail(
        User $user,
        string $ownerNickname,
        string $amount,
        string $rewardToken,
        string $rewardTitle,
        string $slug
    ): void;
    public function sendRewardNewParticipantMail(
        User $user,
        string $rewardToken,
        string $rewardTitle,
        string $slug
    ): void;
    public function sendRewardNewVolunteerMail(
        User $user,
        string $rewardToken,
        string $rewardTitle,
        string $slug
    ): void;
    public function sendRewardNewMail(
        User $user,
        string $rewardToken,
        string $rewardTitle,
        string $rewardType,
        string $slug
    ): void;
    public function sendMarketCreatedMail(
        User $user,
        string $tokenName,
        string $cryptoSymbol
    ): void;
    public function sendNewBuyOrderMail(User $owner, User $maker, string $tokenName, string $cryptoSymbol): void;
    public function sendFailedLoginBlock(User $user): void;
    public function sendVerificationCode(User $user, string $code, string $subject, ?string $to = null): void;
}
