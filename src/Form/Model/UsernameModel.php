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
    private $username;
}
