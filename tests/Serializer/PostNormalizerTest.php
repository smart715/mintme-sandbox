<?php declare(strict_types = 1);

namespace App\Tests\Serializer;

use App\Entity\Post;
use App\Entity\Profile;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Serializer\PostNormalizer;
use FOS\UserBundle\Model\UserInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class PostNormalizerTest extends TestCase
{
    public function testNormalizeWithAUserThatIsAlreadyRewarded(): void
    {
        $normalizer = new PostNormalizer(
            $this->mockObjectNormalizer(),
            $this->mockAuthorizationChecker(),
            $this->mockTokenStorage(
                $this->mockTokenInterface()
            )
        );

        $post = $this->mockPost(true);

        $this->assertEquals(
            [
                'content' => 'TEST',
                'isUserAlreadyRewarded' => true,
                'isUserAlreadyLiked' => true,
                'token' => [
                    'content' => 'TEST',
                ],
            ],
            $normalizer->normalize($post)
        );
    }

    public function testNormalizeWithAUserIsntAlreadyRewarded(): void
    {
        $normalizer = new PostNormalizer(
            $this->mockObjectNormalizer(),
            $this->mockAuthorizationChecker(),
            $this->mockTokenStorage($this->mockTokenInterface())
        );

        $post = $this->mockPost(false);

        $this->assertEquals(
            [
                'content' => 'TEST',
                'isUserAlreadyRewarded' => false,
                'isUserAlreadyLiked' => false,
                'token' => [
                    'content' => 'TEST',
                ],
            ],
            $normalizer->normalize($post)
        );
    }

    public function testNormalizeWithNoUser(): void
    {
        $normalizer = new PostNormalizer(
            $this->mockObjectNormalizer(),
            $this->mockAuthorizationChecker(),
            $this->mockTokenStorage($this->mockTokenInterface(false))
        );

        $post = $this->mockPost();

        $this->assertEquals(
            [
                'content' => 'TEST',
                'isUserAlreadyRewarded' => false,
                'isUserAlreadyLiked' => false,
                'token' => [
                    'content' => 'TEST',
                ],
            ],
            $normalizer->normalize($post)
        );
    }

    public function testNormalizeWithNoSecurityToken(): void
    {
        $normalizer = new PostNormalizer(
            $this->mockObjectNormalizer(),
            $this->mockAuthorizationChecker(),
            $this->mockTokenStorage()
        );

        $post = $this->mockPost();

        $this->assertEquals(
            [
                'content' => 'TEST',
                'isUserAlreadyRewarded' => false,
                'isUserAlreadyLiked' => false,
                'token' => [
                    'content' => 'TEST',
                ],
            ],
            $normalizer->normalize($post)
        );
    }

    public function testNormalizeWithProfile(): void
    {
        $normalizer = new PostNormalizer(
            $this->mockObjectNormalizer(),
            $this->mockAuthorizationChecker(),
            $this->mockTokenStorage($this->mockTokenInterface())
        );

        $post = $this->mockPost(false, true);

        $this->assertEquals(
            [
                'content' => 'TEST',
                'isUserAlreadyRewarded' => false,
                'isUserAlreadyLiked' => false,
                'token' => [
                    'content' => 'TEST',
                ],
                'author' => [
                    'content' => 'TEST',
                ],
            ],
            $normalizer->normalize($post)
        );
    }

    public function testNormalizeWithNoPermissions(): void
    {
        $normalizer = new PostNormalizer(
            $this->mockObjectNormalizer(),
            $this->mockAuthorizationChecker(false),
            $this->mockTokenStorage($this->mockTokenInterface())
        );

        $post = $this->mockPost();

        $this->assertEquals(
            [
                'content' => null,
                'isUserAlreadyRewarded' => false,
                'isUserAlreadyLiked' => false,
                'token' => [
                    'content' => 'TEST',
                ],
            ],
            $normalizer->normalize($post)
        );
    }

    public function testSupportsNormalizationSuccess(): void
    {
        $normalizer = new PostNormalizer(
            $this->mockObjectNormalizer(),
            $this->mockAuthorizationChecker(),
            $this->mockTokenStorage()
        );

        $post = $this->createMock(Post::class);

        $this->assertTrue($normalizer->supportsNormalization($post));
    }

    public function testSupportsNormalizationFailure(): void
    {
        $normalizer = new PostNormalizer(
            $this->mockObjectNormalizer(),
            $this->mockAuthorizationChecker(),
            $this->mockTokenStorage()
        );

        $this->assertFalse($normalizer->supportsNormalization(new \stdClass()));
    }

    private function mockObjectNormalizer(): ObjectNormalizer
    {
        $objectNormalizer = $this->createMock(ObjectNormalizer::class);
        $objectNormalizer->method('normalize')->willReturnCallback(function ($object, $format, $context): array {
            if ($object instanceof Token || $object instanceof Profile) {
                $this->assertEquals('API_BASIC', $context['groups']);
            }

            return ['content' => 'TEST'];
        });

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

    private function mockAuthorizationChecker(bool $isGranted = true): AuthorizationCheckerInterface
    {
        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker->method('isGranted')->willReturn($isGranted);

        return $authorizationChecker;
    }

    private function mockPost(bool $isAlreadyRewarded = false, bool $profileExists = false): Post
    {
        $post = $this->createMock(Post::class);
        $post->method('isUserAlreadyRewarded')->willReturn($isAlreadyRewarded);
        $post->method('isUserAlreadyLiked')->willReturn($isAlreadyRewarded);
        $post->method('getToken')->willReturn($this->mockToken());
        $post->method('getAuthor')->willReturn($profileExists ? $this->mockProfile() : null);

        return $post;
    }

    private function mockToken(): Token
    {
        return $this->createMock(Token::class);
    }

    private function mockProfile(): Profile
    {
        return $this->createMock(Profile::class);
    }
}
