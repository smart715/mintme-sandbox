<?php declare(strict_types = 1);

namespace App\Request\ParamConverter;

use App\Entity\Comment;
use App\Manager\CommentManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CommentConverter implements ParamConverterInterface
{
    /** @var CommentManagerInterface */
    private $commentManager;

    public function __construct(CommentManagerInterface $commentManager)
    {
        $this->commentManager = $commentManager;
    }

    public function apply(Request $request, ParamConverter $configuration): bool
    {
        $comment = $this->commentManager->getById((int)$request->get('commentId'));

        if (!$comment) {
            throw new NotFoundHttpException('Comment not found');
        }

        $request->attributes->set($configuration->getName(), $comment);

        return true;
    }

    /** @codeCoverageIgnore */
    public function supports(ParamConverter $configuration): bool
    {
        return Comment::class === $configuration->getClass();
    }
}
