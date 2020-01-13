<?php

namespace App\Entity\Api;

use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use FOS\OAuthServerBundle\Entity\RefreshToken as BaseRefreshToken;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Api\RefreshTokenRepository")
 * @ORM\Table(name="api__refresh_token")
 */
class RefreshToken extends BaseRefreshToken
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Api\Client")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE"))
     */
    protected $client;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $user;

}
