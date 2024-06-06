<?php declare(strict_types = 1);

namespace App\Tests\Security;

use App\Security\HackerVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class HackerVoterTest extends TestCase
{
    /** @dataProvider voteAttributesProvider */
    public function testVoteOnAttribute(string $attribute, string $host, bool $isHackerAllowed, bool $result): void
    {
        $voter = new HackerVoter($this->mockRequestStack($host), $isHackerAllowed);
        $voteOnAttributeMethod = $this->getMethod('voteOnAttribute');

        $this->assertEquals(
            $result,
            $voteOnAttributeMethod->invokeArgs(
                $voter,
                [$attribute, new \stdClass(), $this->mockTokenInterface()]
            )
        );
    }

    /** @dataProvider attributesProvider */
    public function testSupportedAttributes(string $attribute, bool $result): void
    {
        $voter = new HackerVoter($this->mockRequestStack(), true);
        $supportsAttributeMethod = $this->getMethod('supports');

        $this->assertEquals($result, $supportsAttributeMethod->invokeArgs($voter, [$attribute, new \stdClass()]));
    }

    public function voteAttributesProvider(): array
    {
        return [
            "Hacker is allowed if host is localhost and hacker mode is on" => [
                'hacker',
                'host' => 'localhost',
                'isHackerAllowed' => true,
                'result' => true,
            ],
            "Hacker is not allowed if host is mintme and hacker mode is off" => [
                'hacker',
                'host' => 'www.blank.mintme.abchosting.org',
                'isHackerAllowed' => false,
                'result' => false,
            ],
            "Hacker is not allowed if host is localhost and hacker mode is off" => [
                'hacker',
                'host' => 'localhost',
                'isHackerAllowed' => false,
                'result' => false,
            ],
            "Hacker is not allowed if host is not localhost or mintme" => [
                'hacker',
                'host' => 'not-localhost',
                'isHackerAllowed' => true,
                'result' => false,
            ],
        ];
    }

    public function attributesProvider(): array
    {
        return [
            "Hacker is a supported attribute" => [
                'hacker',
                true,
            ],
            "Other attributes are not supported" => [
                'other',
                false,
            ],
        ];
    }

    private function mockRequestStack(?string $host = null): RequestStack
    {
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getCurrentRequest')->willReturn($this->mockRequest($host));

        return $requestStack;
    }

    private function mockRequest(?string $host): Request
    {
        $request = $this->createMock(Request::class);
        $request->method('getHttpHost')->willReturn($host);

        return $request;
    }

    private function mockTokenInterface(): TokenInterface
    {
        return $this->createMock(TokenInterface::class);
    }

    private function getMethod(string $methodName): \ReflectionMethod
    {
        $reflectionClass = new \ReflectionClass(HackerVoter::class);
        $method = $reflectionClass->getMethod($methodName);
        $method->setAccessible(true);

        return $method;
    }
}
