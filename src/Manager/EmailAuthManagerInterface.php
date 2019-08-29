<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\User;
use App\Manager\Model\EmailAuthResultModel;

interface EmailAuthManagerInterface
{
    public function checkCode(User $user, string $code): EmailAuthResultModel;
    public function generateCode(User $user, int $expirationTime): string;
}
