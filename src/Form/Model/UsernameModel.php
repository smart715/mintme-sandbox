<?php

namespace App\Form\Model;

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
     */
    protected $username;

    public function __construct(string $username)
    {
        $this->username = $username;
    }

    public function getUsername(): string
    {
        return (string)$this->username;
    }

    public function setUsername(?string $username): void
    {
        $this->username = $username;
    }
}
