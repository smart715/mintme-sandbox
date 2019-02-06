<?php

namespace App\Form\Model;

use App\Validator\Constraints\UserEmail;
use Symfony\Component\Validator\Constraints as Assert;

class UsernameModel
{
    /**
     * @var string|null
     * @Assert\NotBlank()
     * @Assert\Email(
     *     message = "Invalid email address.",
     *     checkMX = true
     * )
     * @UserEmail()
     */
    private $username;

    public function __construct(string $username)
    {
        $this->username = $username;
    }

    public function getusername(): string
    {
        return (string)$this->username;
    }

    public function setusername(?string $username): void
    {
        $this->username = $username;
    }
}
