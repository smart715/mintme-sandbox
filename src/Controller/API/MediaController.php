<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Entity\Image;
use App\Entity\User;
use App\Exception\ApiBadRequestException;
use App\Exception\NotFoundProfileException;
use App\Exception\NotFoundTokenException;
use App\Form\AvatarType;
use App\Manager\ImageManagerInterface;
use App\Manager\ProfileManagerInterface;
use App\Manager\TokenManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Exception\InvalidParameterException;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Rest\Route("/api/media")
 */
class MediaController extends AbstractFOSRestController
{
    /** @var EntityManagerInterface */
    protected $em;

    protected TokenManagerInterface $tokenManager;

    public function __construct(EntityManagerInterface $em, TokenManagerInterface $tokenManager)
    {
        $this->em = $em;
        $this->tokenManager = $tokenManager;
    }

    /**
     * @Rest\View()
     * @Rest\Post("/upload", name="media_upload", options={"expose"=true})
     * @Rest\FileParam(name="file", nullable=false)
     * @Rest\RequestParam(name="type", nullable=false)
     * @Rest\RequestParam(name="token", nullable=true)
     */
    public function upload(
        ParamFetcherInterface $request,
        ImageManagerInterface $imageManager,
        ProfileManagerInterface $profileManager
    ): View {
        /** @var User $user */
        $user = $this->getUser();
        $profile = $profileManager->getProfile($user);

        $image = new Image();
        $form = $this->createForm(AvatarType::class, $image);

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
            case 'profile':
                $entity = $profile;

                if (!$entity) {
                    throw new NotFoundProfileException();
                }

                break;
            case 'token':
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
        $image = $imageManager->upload($file, 'avatar');

        $entity->setImage($image);
        $this->em->persist($entity);

        $this->em->flush();

        return $this->view(['image' => $image->getUrl()], Response::HTTP_OK);
    }
}
