<?php declare(strict_types = 1);

namespace App\Mailer;

use App\Entity\PendingWithdraw;
use App\Entity\User;

interface MailerInterface
{
    public function sendWithdrawConfirmationMail(User $user, PendingWithdraw $withdrawData): void;
}
