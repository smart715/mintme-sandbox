<?php declare(strict_types = 1);

namespace App\Mailer;

use App\Entity\PendingWithdrawInterface;
use App\Entity\Profile;
use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
use App\Entity\User;
use App\Entity\UserLoginInfo;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;

interface MailerInterface
{
    public function sendWithdrawConfirmationMail(User $user, PendingWithdrawInterface $withdrawData): void;
    public function sendAuthCodeToMail(string $subject, string $label, TwoFactorInterface $user): void;
    public function sendTransactionCompletedMail(User $user, string $eventName): void;
    public function checkConnection(): void;
    public function sendTokenDeletedMail(Token $token): void;
    public function sendPasswordResetMail(User $user, bool $resetting): void;
    public function sendNewDeviceDetectedMail(User $user, UserLoginInfo $userLoginInfo): void;
    public function sendProfileFillingReminderMail(User $user): void;
    public function sendTokenDescriptionReminderMail(Token $token): void;
    public function sendNewInvestorMail(Token $token, string $newInvestor): void;
    public function sendNewPostMail(User $user, String $tokenName, String $slug): void;
    public function sendTokenDeployedMail(User $user, String $tokenName): void;
    public function sendNoOrdersMail(User $user, String $tokenName): void;
    public function sendKnowledgeBaseMail(User $user, Token $token): void;
    public function sendTokenMarketingTipMail(User $user, string $kbLink): void;
    public function sendMintmeHostMail(User $user, string $price, string $freeDays, string $mintmeHostPath): void;
    public function sendOwnTokenDeployedMail(User $user, string $tokenName, string $txHash): void;
}
