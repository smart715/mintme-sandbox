<?php declare(strict_types = 1);

namespace App\Entity\Api;

use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use FOS\OAuthServerBundle\Entity\AuthCode as BaseAuthCode;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Api\AuthCodeRepository")
 * @ORM\Table(name="api__auth_code")
 */
class AuthCode extends BaseAuthCode
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @var int
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Api\Client")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     * @var Client
     */
    protected $client;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     * @var User
     */
    protected $user;
}
