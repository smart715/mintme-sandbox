<?php

namespace App\Manager;

use App\Entity\User;

interface UserManagerInterface extends \FOS\UserBundle\Model\UserManagerInterface
{
    public function find(int $id): ?User;
}
