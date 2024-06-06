<?php declare(strict_types = 1);

namespace App\Tests\Security;

use App\Config\UserLimitsConfig;
use App\Entity\User;
use App\Security\OAuthVoter;
use PHPUnit\Framework\TestCase;
use SplFixedArray;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class OAuthVoterTest extends TestCase
{
    /** @dataProvider voteAttributesProvider */
    public function testVoteOnAttribute(string $attribute, ?User $user, int $maxApiClients, bool $result): void
    {
        $voter = new OAuthVoter($this->mockUserLimitsConfig($maxApiClients));
        $voteOnAttributeMethod = $this->getMethod('voteOnAttribute');

        $this->assertEquals(
            $result,
            $voteOnAttributeMethod->invokeArgs(
                $voter,
                [$attribute, new \stdClass(), $this->mockTokenInterface($user)]
            )
        );
    }

    /** @dataProvider attributesProvider */
    public function testSupportedAttributes(string $attribute, bool $result): void
    {
        $voter = new OAuthVoter($this->mockUserLimitsConfig());
        $supportsAttributeMethod = $this->getMethod('supports');

        $this->assertEquals($result, $supportsAttributeMethod->invokeArgs($voter, [$attribute, new \stdClass()]));
    }

    public function voteAttributesProvider(): array
    {
        return [
            "OAuth isn't allowed if no user " => [
                'attribute' => 'create-oauth',
                'user' => null,
                'maxApiClient' => 99,
                'result' => false,
            ],
            'OAuth isn\'t allowed if wrong attribute' => [
                'attribute' => 'test',
                'user' => $this->mockUser(),
                'maxApiClient' => 99,
                'result' => false,
            ],
            'OAuth is allowed if user has no api clients' => [
                'attribute' => 'create-oauth',
                'user' => $this->mockUser(),
                'maxApiClient' => 99,
                'result' => true,
            ],
            'OAuth is allowed if user has max api clients' => [
                'attribute' => 'create-oauth',
                'user' => $this->mockUser(99),
                'maxApiClient' => 99,
                'result' => false,
            ],
            'OAuth is allowed if user has more than max api clients' => [
                'attribute' => 'create-oauth',
                'user' => $this->mockUser(100),
                'maxApiClient' => 99,
                'result' => false,
            ],
        ];
    }

    public function attributesProvider(): array
    {
        return [
            "Hacker is a supported attribute" => [
                'create-oauth',
                true,
            ],
            "Other attributes are not supported" => [
                'other',
                false,
            ],
        ];
    }

    private function mockTokenInterface(?User $user): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }

    private function getMethod(string $methodName): \ReflectionMethod
    {
        $reflectionClass = new \ReflectionClass(OAuthVoter::class);
        $method = $reflectionClass->getMethod($methodName);
        $method->setAccessible(true);

        return $method;
    }

    private function mockUserLimitsConfig(int $maxApiClients = 0): UserLimitsConfig
    {
        $userLimitsConfig = $this->createMock(UserLimitsConfig::class);
        $userLimitsConfig->method('getMaxClientsLimit')->willReturn($maxApiClients);

        return $userLimitsConfig;
    }

    private function mockUser(int $apiClientsCount = 0): User
    {
        $user = $this->createMock(User::class);
        $user->method('getApiClients')->willReturn(
            (new SplFixedArray($apiClientsCount))->toArray()
        );

        return $user;
    }
}
