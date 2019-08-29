<?php declare(strict_types = 1);

namespace App\Mailer;

use App\Entity\PendingWithdraw;
use App\Entity\User;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;

interface MailerInterface
{
    public function sendWithdrawConfirmationMail(User $user, PendingWithdraw $withdrawData): void;
    public function sendAuthCodeToMail(string $subject, string $label, TwoFactorInterface $user): void;
}
