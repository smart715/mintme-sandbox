<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Communications\SMS\D7NetworksCommunicatorInterface;
use App\Communications\SMS\Model\SMS;
use App\Entity\User;
use App\Exception\ApiBadRequestException;
use App\Logger\UserActionLogger;
use App\Manager\PhoneNumberManagerInterface;
use App\Utils\RandomNumberInterface;
use App\Validator\Constraints\AddPhoneNumber;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Sirprize\PostalCodeValidator\Validator as PostalCodeValidator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Rest\Route("/api/profile")
 */
class ProfileController extends AbstractFOSRestController
{
    private TranslatorInterface $translator;
    private EntityManagerInterface $entityManager;
    private UserActionLogger $userActionLogger;

    public function __construct(
        TranslatorInterface $translator,
        EntityManagerInterface $entityManager,
        UserActionLogger $userActionLogger
    ) {
        $this->translator = $translator;
        $this->entityManager = $entityManager;
        $this->userActionLogger = $userActionLogger;
    }

    /**
     * @Rest\View()
     * @Rest\Post("/validate-zip-code", name="validate_zip_code", options={"expose"=true})
     * @Rest\RequestParam(name="country", nullable=true)
     */
    public function validateZipCode(ParamFetcherInterface $request): View
    {
        $country = $request->get('country');

        if (null === $country) {
            throw new ApiBadRequestException('Invalid request');
        }

        $validator = new PostalCodeValidator();
        $finalPattern = '';
        $hasPattern = '' === $country
            ? false
            : $validator->hasCountry(mb_strtoupper($country));

        if ($hasPattern) {
            $patterns = $validator->getFormats(mb_strtoupper($country));

            if (0 === count($patterns)) {
                $hasPattern = false;
            } else {
                $search = ['#', '@', ' '];
                $replace = ['\d', '[a-z]', '\s'];

                foreach ($patterns as &$pattern) {
                    $pattern = '(' . str_replace($search, $replace, $pattern) . ')';
                }

                $finalPattern = implode('|', $patterns);

                if (count($patterns) > 1) {
                    $finalPattern = '(' . $finalPattern . ')';
                }
            }
        }

        return $this->view(['hasPattern' => $hasPattern, 'pattern' => $finalPattern], Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Get("/send-phone-verification-code", name="send_phone_verification_code", options={"expose"=true})
     * @param D7NetworksCommunicatorInterface $d7NetworksCommunicator
     * @param PhoneNumberUtil $numberUtil
     * @param RandomNumberInterface $randomNumber
     * @return View
     * @throws \Exception
     */
    public function sendPhoneVerificationCode(
        D7NetworksCommunicatorInterface $d7NetworksCommunicator,
        PhoneNumberUtil $numberUtil,
        RandomNumberInterface $randomNumber,
        PhoneNumberManagerInterface $phoneNumberManager,
        ValidatorInterface $validator
    ): View {
        $this->denyAccessUnlessGranted('not-blocked');

        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user || !$user->getProfile()->getPhoneNumber()) {
            throw $this->createAccessDeniedException();
        }

        $phoneNumber = $user->getProfile()->getPhoneNumber();
        $totalLimit = $this->getParameter('adding_phone_attempts_limit')['overall'];

        if ($totalLimit <= $phoneNumber->getTotalAttempts()) {
            $user->setIsBlocked(true);
            $phoneNumber->setTotalAttempts(0);
            $this->entityManager->persist($user);
            $this->entityManager->persist($phoneNumber);
            $this->entityManager->flush();

            throw $this->createAccessDeniedException();
        }

        $phoneNumber->setVerificationCode($randomNumber->generateVerificationCode());

        $addPhoneNumberConstraint = new AddPhoneNumber();
        $errors = $validator->validate($phoneNumber->getPhoneNumber(), $addPhoneNumberConstraint);

        if (count($errors) > 0) {
            return $this->view(['error' => $errors[0]->getMessage()], Response::HTTP_OK);
        }

        $sms = new SMS(
            'MINTME',
            $numberUtil->format($phoneNumber->getPhoneNumber(), PhoneNumberFormat::E164),
            $this->translator->trans(
                'phone_confirmation.your_verification_code',
                ['%code%' => $phoneNumber->getVerificationCode()]
            )
        );

        try {
            $response = $d7NetworksCommunicator->send($sms);
            $this->userActionLogger->info(
                'Phone number verification code requested.',
                ['to' => $sms->getTo(), $response]
            );
        } catch (\Throwable $e) {
            $this->userActionLogger->info('Error during send phone number code verificaion'. json_encode($e));

            throw new \Exception($this->translator->trans('api.something_went_wrong'));
        }

        if (!$phoneNumber->getEditDate()) {
            $phoneNumber = $phoneNumberManager->updateNumberAndAddingAttempts($phoneNumber);
        } else {
            $phoneNumber->setEditAttempts($phoneNumber->getEditAttempts()+1);
        }

        $this->entityManager->persist($phoneNumber);
        $this->entityManager->flush();

        return $this->view([], Response::HTTP_OK);
    }
}
