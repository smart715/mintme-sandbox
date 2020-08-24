<?php declare(strict_types = 1);

namespace App\Mailer;

use App\Entity\PendingWithdrawInterface;
use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
use App\Entity\User;
use App\Entity\UserLoginInfo;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;

interface MailerInterface
{
    public function sendWithdrawConfirmationMail(User $user, PendingWithdrawInterface $withdrawData): void;
    public function sendAuthCodeToMail(string $subject, string $label, TwoFactorInterface $user): void;
    public function sendTransactionCompletedMail(TradebleInterface $tradable, User $user, string $amount, string $eventName): void;
    public function checkConnection(): void;
    public function sendTokenDeletedMail(Token $token): void;
    public function sendPasswordResetMail(User $user, bool $resetting): void;
    public function sendNewDeviceDetectedMail(User $user, UserLoginInfo $userLoginInfo): void;
    public function sendProfileFillingReminderMail(User $user): void;
    public function sendTokenDescriptionReminderMail(User $user): void;
}
