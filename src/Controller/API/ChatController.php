<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Controller\Traits\ViewOnlyTrait;
use App\Entity\User;
use App\Exception\ApiForbiddenException;
use App\Exception\ApiNotFoundException;
use App\Manager\MessageManagerInterface;
use App\Manager\ThreadManagerInterface;
use App\Manager\UserManagerInterface;
use App\Services\TranslatorService\TranslatorInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @Rest\Route("/api/chat")
 */
class ChatController extends AbstractFOSRestController
{
    private TranslatorInterface $translation;
    private UserManagerInterface $userManager;
    private ThreadManagerInterface $threadManager;
    protected SessionInterface $session;
    private NormalizerInterface $normalizer;
    private MessageManagerInterface $messageManager;

    use ViewOnlyTrait;

    public function __construct(
        TranslatorInterface $translation,
        ThreadManagerInterface $threadManager,
        UserManagerInterface $userManager,
        SessionInterface $session,
        NormalizerInterface $normalizer,
        MessageManagerInterface $messageManager
    ) {
        $this->translation = $translation;
        $this->threadManager = $threadManager;
        $this->userManager = $userManager;
        $this->session = $session;
        $this->normalizer = $normalizer;
        $this->messageManager = $messageManager;
    }

    /**
     * @Rest\View()
     * @Rest\Post("/block-user", name="block_user", options={"expose"=true})
     * @Rest\RequestParam(name="threadId", nullable=false, requirements="\d+")
     * @Rest\RequestParam(name="participantId", nullable=false, requirements="\d+")
     * @Rest\RequestParam(name="isBlocked", nullable=false)
     */
    public function blockUser(ParamFetcherInterface $request): View
    {
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

        /** @var User $user */
        $user = $this->getUser();
        $threadId = $request->get('threadId');
        $isBlocked = $request->get('isBlocked');
        /** @var User|null $participant */
        $participant = $this->userManager->find($request->get('participantId'));

        if (!$participant) {
            throw new ApiNotFoundException(
                $this->translation->trans('chat.user_not_found')
            );
        }

        if ($participant->getId() === $user->getId()) {
            throw new ApiNotFoundException(
                $this->translation->trans('chat.cannot_block_owner')
            );
        }

        $thread = $this->threadManager->find($threadId);

        if (!$thread || !$thread->hasParticipant($user) || !$thread->hasParticipant($participant)) {
            throw new ApiNotFoundException(
                $this->translation->trans('chat.user_not_found')
            );
        }

        $threadMetaData = $thread->getMetadata();
        $this->threadManager->toggleBlockUser($threadMetaData, $participant);
        $params = ['%nickname%' => $participant->getNickname()];
        $response = ['message' => true === $isBlocked ?
            $this->translation->trans('chat.user_unblocked', $params) :
            $this->translation->trans('chat.user_blocked', $params)];

        return $this->view($response, Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/delete-chat", name="delete_chat", options={"expose"=true})
     * @Rest\RequestParam(name="threadId", nullable=false, requirements="\d+")
     * @Rest\RequestParam(name="participantId", nullable=false, requirements="\d+")
     */
    public function deleteChat(ParamFetcherInterface $request): View
    {
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

        /** @var User $user */
        $user = $this->getUser();
        $threadId = $request->get('threadId');
        $thread = $this->threadManager->find($threadId);
        $participantId = $request->get('participantId');
        /** @var User|null $participant */
        $participant = $this->userManager->find($participantId);

        if (!$participant) {
            throw new ApiNotFoundException(
                $this->translation->trans('chat.user_not_found')
            );
        }

        if ($participant->getId() === $user->getId()) {
            throw new ApiNotFoundException(
                $this->translation->trans('chat.cannot_delete_owner')
            );
        }

        if (!$thread || !$thread->hasParticipant($user) || !$thread->hasParticipant($participant)) {
            throw new ApiNotFoundException(
                $this->translation->trans('chat.user_not_found')
            );
        }

        $threadMetaData = $thread->getMetadata();
        $this->messageManager->setDeleteMessages($thread, $user);
        $this->threadManager->toggleHiddenThread($threadMetaData, $user);
        $areAllThreadsHidden = $this->threadManager->areAllThreadsHidden($threadMetaData);

        if ($areAllThreadsHidden) {
            $this->threadManager->delete($threadId);
        }

        return $this->view([
            'message' => $this->translation->trans('chat.deleted'),
        ], Response::HTTP_OK);
    }
    /**
     * @Rest\View()
     * @Rest\Get("/get-contacts", name="get_contacts", options={"expose"=true})
     */
    public function getContacts(ParamFetcherInterface $request): View
    {
        /** @var User $user */
        $user = $this->getUser();

        $threads = $this->threadManager->traderThreads($user);
        $groups = ['Default', 'API'];
        $context = ['groups' => $groups];
        $contacts = $this->normalizer->normalize($threads, null, $context);
        $response = ['contactList' => $contacts];

        return $this->view($response, Response::HTTP_OK);
    }
}
