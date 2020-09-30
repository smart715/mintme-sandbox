<?php declare(strict_types = 1);

namespace App\Tests\Request\ParamConverter;

use App\Entity\Comment;
use App\Manager\CommentManagerInterface;
use App\Request\ParamConverter\CommentConverter;
use PHPUnit\Framework\TestCase;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CommentConverterTest extends TestCase
{
    public function testApply(): void
    {
        $c = $this->createMock(Comment::class);

        $cm = $this->createMock(CommentManagerInterface::class);
        $cm->method('getById')->willReturn($c);

        $req = $this->createMock(Request::class);
        $req->attributes = $this->createMock(ParameterBag::class);

        $converter = new CommentConverter($cm);
        $res = $converter->apply(
            $req,
            $this->createMock(ParamConverter::class)
        );
        $this->assertTrue($res);
    }

    public function testApplyFalse(): void
    {
        $cm = $this->createMock(CommentManagerInterface::class);
        $cm->method('getById')->willReturn(null);

        $converter = new CommentConverter($cm);

        $this->expectException(NotFoundHttpException::class);

        $converter->apply(
            $this->createMock(Request::class),
            $this->createMock(ParamConverter::class)
        );
    }
}
