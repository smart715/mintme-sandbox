<?php declare(strict_types = 1);

namespace App\Tests\Serializer;

use App\Entity\AirdropCampaign\Airdrop;
use App\Entity\AirdropCampaign\AirdropAction;
use App\Entity\User;
use App\Serializer\AirdropNormalizer;
use Doctrine\Common\Collections\ArrayCollection;
use FOS\UserBundle\Model\UserInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class AirdropNormalizerTest extends TestCase
{
    public function testNormalize(): void
    {
        $normalizer = new AirdropNormalizer(
            $this->mockObjectNormalizer(),
            $this->mockTokenStorage(
                $this->mockTokenInterface(true)
            )
        );
        $airdrop = $this->mockAirdrop();

        $this->assertEquals(
            [
                'actions' => [
                    'twitterRetweet' => [
                        'id' => 1,
                        'done' => false,
                    ],
                ],
                'actionsData' => [
                    'twitterRetweet' => 'TEST',
                ],
            ],
            $normalizer->normalize($airdrop)
        );
    }

    public function testNormalizeWithNoUser(): void
    {
        $normalizer = new AirdropNormalizer(
            $this->mockObjectNormalizer(),
            $this->mockTokenStorage(
                $this->mockTokenInterface(false)
            )
        );
        $airdrop = $this->mockAirdrop();

        $this->assertEquals(
            [
                'actions' => [
                    'twitterRetweet' => [
                        'id' => 1,
                        'done' => false,
                    ],
                ],
                'actionsData' => [
                    'twitterRetweet' => 'TEST',
                ],
            ],
            $normalizer->normalize($airdrop)
        );
    }

    public function testSupportsNormalizationSuccess(): void
    {
        $normalizer = new AirdropNormalizer($this->mockObjectNormalizer(), $this->mockTokenStorage());
        $airdrop = $this->createMock(Airdrop::class);

        $this->assertTrue($normalizer->supportsNormalization($airdrop));
    }

    public function testSupportsNormalizationFailure(): void
    {
        $normalizer = new AirdropNormalizer($this->mockObjectNormalizer(), $this->mockTokenStorage());

        $this->assertFalse($normalizer->supportsNormalization(new \stdClass()));
    }

    private function mockObjectNormalizer(): ObjectNormalizer
    {
        $objectNormalizer = $this->createMock(ObjectNormalizer::class);
        $objectNormalizer->method('normalize')->willReturn([]);

        return $objectNormalizer;
    }

    private function mockTokenStorage(?TokenInterface $token = null): TokenStorageInterface
    {
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

        return $tokenStorage;
    }

    private function mockTokenInterface(bool $isUserInstance = true): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')
            ->willReturn($isUserInstance ? $this->mockUser() : $this->mockUserInterface());

        return $token;
    }

    private function mockUser(): User
    {
        return $this->createMock(User::class);
    }

    private function mockUserInterface(): UserInterface
    {
        return $this->createMock(UserInterface::class);
    }

    private function mockAirdrop(): Airdrop
    {
        $airdrop = $this->createMock(Airdrop::class);

        $actions = new ArrayCollection([
           $this->mockAction(),
           $this->mockAction(),
           $this->mockAction(),
        ]);

        $airdrop->expects($this->once())->method('getActions')->willReturn($actions);

        return $airdrop;
    }

    private function mockAction(): AirdropAction
    {
        $action = $this->createMock(AirdropAction::class);
        $action->method('getType')->willReturn(1);
        $action->method('getId')->willReturn(1);
        $action->method('getData')->willReturn('TEST');
        $action->method('getUsers')->willReturn(
            new ArrayCollection([
                $this->mockUser(),
                $this->mockUser(),
                $this->mockUser(),
            ])
        );

        return $action;
    }
}
