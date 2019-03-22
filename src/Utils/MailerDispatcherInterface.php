<?php declare(strict_types = 1);

namespace App\Utils;

use FOS\UserBundle\Model\UserInterface;

interface MailerDispatcherInterface
{
    public function sendEmailConfirmation(UserInterface $user): void;
    public function sendResettingEmail(UserInterface $user): void;
}
