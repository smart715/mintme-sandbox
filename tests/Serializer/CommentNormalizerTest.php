<?php declare(strict_types = 1);

namespace App\Tests\Serializer;

use App\Entity\Comment;
use App\Entity\CommentTip;
use App\Entity\Like;
use App\Entity\User;
use App\Serializer\CommentNormalizer;
use Doctrine\Common\Collections\ArrayCollection;
use FOS\UserBundle\Model\UserInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class CommentNormalizerTest extends TestCase
{
    public function testNormalizeWithEditPermissions(): void
    {
        $comment = $this->mockComment();
        $commentNormalizer = new CommentNormalizer(
            $this->mockObjectNormalizer(),
            $this->mockAuthorizationChecker(true),
            $this->mockTokenStorage()
        );

        $normalizedComment = $commentNormalizer->normalize($comment);

        $this->assertEquals(
            [
                'editable' => true,
                'deletable' => false,
                'liked' => false,
                'tipped' => false,
                'tips' => [],
                'content' => null,
            ],
            $normalizedComment
        );
    }

    public function testNormalizeWithDeletePermissions(): void
    {
        $comment = $this->mockComment();
        $commentNormalizer = new CommentNormalizer(
            $this->mockObjectNormalizer(),
            $this->mockAuthorizationChecker(false, true),
            $this->mockTokenStorage()
        );

        $normalizedComment = $commentNormalizer->normalize($comment);

        $this->assertEquals(
            [
                'editable' => false,
                'deletable' => true,
                'liked' => false,
                'tipped' => false,
                'tips' => [],
                'content' => null,
            ],
            $normalizedComment
        );
    }

    public function testNormalizeWithNoSecurityToken(): void
    {
        $comment = $this->mockComment();
        $commentNormalizer = new CommentNormalizer(
            $this->mockObjectNormalizer(),
            $this->mockAuthorizationChecker(),
            $this->mockTokenStorage()
        );

        $normalizedComment = $commentNormalizer->normalize($comment);

        $this->assertEquals(
            [
                'editable' => false,
                'deletable' => false,
                'liked' => false,
                'tipped' => false,
                'tips' => [],
                'content' => null,
            ],
            $normalizedComment
        );
    }

    public function testNormalizeWithNoUser(): void
    {
        $comment = $this->mockComment();
        $commentNormalizer = new CommentNormalizer(
            $this->mockObjectNormalizer(),
            $this->mockAuthorizationChecker(),
            $this->mockTokenStorage($this->mockTokenInterface())
        );

        $normalizedComment = $commentNormalizer->normalize($comment);

        $this->assertEquals(
            [
                'editable' => false,
                'deletable' => false,
                'liked' => false,
                'tipped' => false,
                'tips' => [],
                'content' => null,
            ],
            $normalizedComment
        );
    }

    public function testNormalizeWithUserThatLikedTheComment(): void
    {
        $user = $this->mockUser();
        $comment = $this->mockComment(true);
        $commentNormalizer = new CommentNormalizer(
            $this->mockObjectNormalizer(),
            $this->mockAuthorizationChecker(),
            $this->mockTokenStorage(
                $this->mockTokenInterface($user)
            )
        );

        $normalizedComment = $commentNormalizer->normalize($comment);

        $this->assertEquals(
            [
                'editable' => false,
                'deletable' => false,
                'liked' => true,
                'tipped' => false,
                'tips' => [],
                'content' => null,
            ],
            $normalizedComment
        );
    }
    public function testNormalizeWithTips(): void
    {
        $comment = $this->mockComment(false, [$this->mockTip(), $this->mockTip()]);
        $commentNormalizer = new CommentNormalizer(
            $this->mockObjectNormalizer(),
            $this->mockAuthorizationChecker(),
            $this->mockTokenStorage($this->mockTokenInterface())
        );

        $normalizedComment = $commentNormalizer->normalize($comment);

        $this->assertEquals(
            [
                'editable' => false,
                'deletable' => false,
                'liked' => false,
                'tipped' => false,
                'tips' => [
                    0 => ['content' => 'tip'],
                    1 => ['content' => 'tip'],
                ],
                'content' => null,
            ],
            $normalizedComment
        );
    }

    public function testNormalizeWithUserWithTipsAndCommentIsTipped(): void
    {
        $user = $this->mockUser();
        $comment = $this->mockComment(false, [$this->mockTip($user)]);
        $commentNormalizer = new CommentNormalizer(
            $this->mockObjectNormalizer(),
            $this->mockAuthorizationChecker(),
            $this->mockTokenStorage(
                $this->mockTokenInterface($user)
            )
        );

        $normalizedComment = $commentNormalizer->normalize($comment);

        $this->assertEquals(
            [
                'editable' => false,
                'deletable' => false,
                'liked' => false,
                'tipped' => true,
                'tips' => [
                    0 => ['content' => 'tip'],
                ],
                'content' => null,
            ],
            $normalizedComment
        );
    }

    public function testSupportsNormalizationSuccess(): void
    {
        $comment = $this->createMock(Comment::class);
        $commentNormalizer = new CommentNormalizer(
            $this->mockObjectNormalizer(),
            $this->mockAuthorizationChecker(),
            $this->mockTokenStorage()
        );

        $this->assertTrue($commentNormalizer->supportsNormalization($comment));
    }

    public function testSupportsNormalizationFailure(): void
    {
        $notComment = new \stdClass();
        $commentNormalizer = new CommentNormalizer(
            $this->mockObjectNormalizer(),
            $this->mockAuthorizationChecker(),
            $this->mockTokenStorage()
        );

        $this->assertFalse($commentNormalizer->supportsNormalization($notComment));
    }

    private function mockObjectNormalizer(): ObjectNormalizer
    {
        $objectNormalizer = $this->createMock(ObjectNormalizer::class);
        $objectNormalizer
            ->method('normalize')
            ->willReturnCallback(function ($object, $format, array $context): array {
                if ($object instanceof CommentTip) {
                    $this->assertEquals(['API_BASIC'], $context['groups']);

                    return ['content' => 'tip'];
                }

                return [];
            });

        return $objectNormalizer;
    }

    private function mockTokenStorage(?TokenInterface $token = null): TokenStorageInterface
    {
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

        return $tokenStorage;
    }

    private function mockTokenInterface(?User $user = null): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')
            ->willReturn($user ?? $this->mockUserInterface());

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

    private function mockAuthorizationChecker(
        bool $isEditGranted = false,
        bool $isDeleteGranted = false
    ): AuthorizationCheckerInterface {
        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker
            ->method('isGranted')
            ->willReturnCallback(function (string $attributes) use ($isEditGranted, $isDeleteGranted): bool {
                if ('edit' === $attributes) {
                    return $isEditGranted;
                }

                if ('delete' === $attributes) {
                    return $isDeleteGranted;
                }

                return false;
            });

        return $authorizationChecker;
    }

    private function mockComment(bool $isLiked = false, array $tips = []): Comment
    {
        $comment = $this->createMock(Comment::class);
        $comment->method('getLikedBy')->willReturn($isLiked ? $this->mockLike() : null);
        $comment->method('getTips')->willReturn(new ArrayCollection($tips));

        return $comment;
    }

    private function mockLike(): Like
    {
        return $this->createMock(Like::class);
    }

    private function mockTip(?User $user = null): CommentTip
    {
        $tip = $this->createMock(CommentTip::class);
        $tip->method('getUser')->willReturn($user ?? $this->mockUser());

        return $tip;
    }
}
