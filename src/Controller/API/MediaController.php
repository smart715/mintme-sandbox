<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Controller\Traits\ViewOnlyTrait;
use App\Entity\Image;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exception\ApiBadRequestException;
use App\Exception\ApiForbiddenException;
use App\Exception\NotFoundProfileException;
use App\Exception\NotFoundTokenException;
use App\Form\ImageType;
use App\Manager\ImageManagerInterface;
use App\Manager\ProfileManagerInterface;
use App\Manager\TokenManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Exception\InvalidParameterException;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @Rest\Route("/api/media")
 */
class MediaController extends AbstractFOSRestController
{
    public const PURPOSE_COVER = 'cover';
    public const TYPE_TOKEN = 'token';
    public const TYPE_PROFILE = 'profile';

    protected EntityManagerInterface $em;

    protected TokenManagerInterface $tokenManager;
    protected SessionInterface $session;

    use ViewOnlyTrait;

    public function __construct(EntityManagerInterface $em, TokenManagerInterface $tokenManager, SessionInterface $session)
    {
        $this->em = $em;
        $this->tokenManager = $tokenManager;
        $this->session = $session;
    }

    /**
     * @Rest\View()
     * @Rest\Post("/upload", name="media_upload", options={"expose"=true})
     * @Rest\FileParam(name="file", nullable=false)
     * @Rest\RequestParam(name="type", requirements="(profile|token)", nullable=false)
     * @Rest\RequestParam(name="purpose", requirements="(avatar|cover)", nullable=false)
     * @Rest\RequestParam(name="token", nullable=true)
     */
    public function upload(
        ParamFetcherInterface $request,
        ImageManagerInterface $imageManager,
        ProfileManagerInterface $profileManager,
        ParameterBagInterface $parameterBag
    ): View {
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

        /** @var User $user */
        $user = $this->getUser();
        $profile = $profileManager->getProfile($user);

        $isCover = self::PURPOSE_COVER === $request->get('purpose');

        $image = new Image();
        $form = $this->createForm(
            ImageType::class,
            $image,
            ['is_cover' => $isCover]
        );

        try {
            $form->submit($request->all());
        } catch (InvalidParameterException $exception) {
            throw new ApiBadRequestException($exception->getViolations()[0]->getMessage());
        }

        if (!$form->isValid()) {
            foreach ($form->all() as $childForm) {
                /** @var FormError[] $fieldErrors */
                $fieldErrors = $form->get($childForm->getName())->getErrors();

                if (count($fieldErrors) > 0) {
                    throw new ApiBadRequestException($fieldErrors[0]->getMessage());
                }
            }

            throw new ApiBadRequestException('Invalid argument');
        }

        switch ($form->get('type')->getData()) {
            case self::TYPE_PROFILE:
                if ($isCover) {
                    throw new ApiBadRequestException('Profiles do not support cover images');
                }

                $entity = $profile;

                if (!$entity) {
                    throw new NotFoundProfileException();
                }

                break;
            case self::TYPE_TOKEN:
                $tokenName = $form->get('token')->getData();
                $entity = $this->tokenManager->getOwnTokenByName($tokenName);

                if (!$entity) {
                    throw new NotFoundTokenException();
                }

                break;
            default:
                throw new ApiBadRequestException('Invalid argument');
        }

        $file = $form->get('file')->getData();
        $isTokenCover = $entity instanceof Token && $isCover;

        $image = $imageManager->upload(
            $file,
            $form->get('purpose')->getData()
        );

        if ($isTokenCover) {
            /** @var Token $entity */
            $entity->setCoverImage($image);
        } else {
            $entity->setImage($image);
        }

        $this->em->persist($entity);

        $this->em->flush();

        return $this->view(['image' => $image->getUrl()], Response::HTTP_OK);
    }
}
