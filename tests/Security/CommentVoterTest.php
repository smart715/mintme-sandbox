<?php declare(strict_types = 1);

namespace App\Tests\Security;

use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\Profile;
use App\Entity\User;
use App\Security\CommentVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class CommentVoterTest extends TestCase
{
    /** @dataProvider voteAttributesProvider */
    public function testVoteOnAttribute(
        string $attribute,
        ?User $user,
        User $commentAuthor,
        bool $result,
        ?User $postAuthor = null
    ): void {
        $voter = new CommentVoter();
        $voteOnAttributeMethod = $this->getMethod('voteOnAttribute');

        $this->assertEquals(
            $result,
            $voteOnAttributeMethod->invokeArgs(
                $voter,
                [$attribute, $this->mockComment($commentAuthor, $postAuthor), $this->mockTokenInterface($user)]
            )
        );
    }

    /**
     * @dataProvider attributesProvider
     * @param Comment|mixed $comment
     * @throws \ReflectionException
     */
    public function testSupportedAttributes(string $attribute, $comment, bool $result): void
    {
        $commentVoter = new CommentVoter();
        $supportsAttributeMethod = $this->getMethod('supports');

        $this->assertEquals(
            $result,
            $supportsAttributeMethod->invokeArgs($commentVoter, [$attribute, $comment])
        );
    }

    public function voteAttributesProvider(): array
    {
        return [
            "A user can't edit a comment he didn't create" => [
                'edit',
                $this->mockUser(),
                $this->mockUser(),
                false,
            ],
            "A user can edit his own comment" => [
                'edit',
                $user = $this->mockUser(),
                $user,
                true,
            ],
            "A guest can't edit a comment" => [
                'edit',
                null,
                $this->mockUser(),
                false,
            ],
            "A guest can't delete a comment" => [
                'delete',
                null,
                $this->mockUser(),
                false,
            ],
            "Post author can delete any comment" => [
                'delete',
                $userAndPostAuthor = $this->mockUser(10),
                $this->mockUser(11),
                true,
                $userAndPostAuthor,
            ],
            "A user can't delete a comment he didn't create" => [
                'delete',
                $this->mockUser(10),
                $this->mockUser(11),
                false,
            ],
            "A user can delete a comment he created" => [
                'delete',
                $user = $this->mockUser(),
                $user,
                true,
            ],
            "Unsupported attribute will return false" => [
                'unsupported',
                null,
                $this->mockUser(),
                false,
            ],

        ];
    }

    public function attributesProvider(): array
    {
        return [
            "Right attribute with comment instance is valid" => ['edit', $this->mockComment(), true],
            "Right attribute without comment instance is invalid" => ['edit', new \stdClass() , false],
            "Wrong attribute without comment instance is invalid" => ['test', new \stdClass() , false],
            "Wrong attribute with comment instance is invalid" => ['edit', new \stdClass() , false],

        ];
    }

    private function mockTokenInterface(?User $user): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }

    private function mockComment(?User $user = null, ?User $postAuthor = null): Comment
    {
        $comment =  $this->createMock(Comment::class);
        $comment->method('getAuthor')
            ->willReturn($user ?: $this->mockUser(99999));

        $comment->method('getPost')
            ->willReturn($this->mockPost($postAuthor));

        return $comment;
    }

    private function mockUser(int $id = 1): User
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($id);

        return $user;
    }

    private function mockPost(?User $postAuthor): Post
    {
        $post = $this->createMock(Post::class);
        $post->method('getAuthor')->willReturn($this->mockProfile($postAuthor));

        return $post;
    }

    private function mockProfile(?User $postAuthor): Profile
    {
        $profile = $this->createMock(Profile::class);
        $profile->method('getUser')
            ->willReturn($postAuthor ?: $this->mockUser(9999));

        return $profile;
    }

    private function getMethod(string $methodName): \ReflectionMethod
    {
        $reflectionClass = new \ReflectionClass(CommentVoter::class);
        $method = $reflectionClass->getMethod($methodName);
        $method->setAccessible(true);

        return $method;
    }
}
