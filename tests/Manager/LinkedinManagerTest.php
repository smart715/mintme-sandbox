<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Manager\LinkedinManager;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\LinkedInAccessToken;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;

class LinkedinManagerTest extends TestCase
{
    public function testShareMessage(): void
    {
        $this->expectException(IdentityProviderException::class);

        $accessToken = $this->createMock(LinkedInAccessToken::class);

        $linkedinResource = $this->createMock(ResourceOwnerInterface::class);

        $manager = $this->getMockBuilder(LinkedinManager::class)
            ->onlyMethods(['getAccessToken', 'getUser'])
            ->setConstructorArgs([
                $this->createMock(RouterInterface::class),
                $this->createMock(SessionInterface::class),
                '123',
                'abc',
            ])
            ->getMock();

        $manager
            ->method('getAccessToken')
            ->willReturn($accessToken);

        $manager
            ->method('getUser')
            ->with($accessToken)
            ->willReturn($linkedinResource);

        $manager->shareMessage('helo', 'https://example.co');
    }
}
