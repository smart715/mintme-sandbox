<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Communications\SMS\D7NetworksCommunicatorInterface;
use App\Communications\SMS\Model\SMS;
use App\Entity\User;
use App\Exception\ApiBadRequestException;
use App\Manager\PhoneNumberManagerInterface;
use App\Utils\RandomNumberInterface;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Sirprize\PostalCodeValidator\Validator as PostalCodeValidator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Rest\Route("/api/profile")
 */
class ProfileController extends AbstractFOSRestController
{
    private TranslatorInterface $translator;
    private EntityManagerInterface $entityManager;

    public function __construct(TranslatorInterface $translator, EntityManagerInterface $entityManager)
    {
        $this->translator = $translator;
        $this->entityManager = $entityManager;
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
        PhoneNumberManagerInterface $phoneNumberManager
    ): View {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user || !$user->getProfile()->getPhoneNumber()) {
            $this->createAccessDeniedException();
        }

        $phoneNumber = $user->getProfile()->getPhoneNumber();
        $phoneNumber->setVerificationCode($randomNumber->generateVerificationCode());

        $sms = new SMS(
            'MINTME',
            $numberUtil->format($phoneNumber->getPhoneNumber(), PhoneNumberFormat::E164),
            $this->translator->trans(
                'phone_confirmation.your_verification_code',
                ['%code%' => $phoneNumber->getVerificationCode()]
            )
        );

        try {
            $d7NetworksCommunicator->send($sms);
        } catch (\Throwable $e) {
            throw new \Exception($this->translator->trans('api.something_went_wrong'));
        }

        $phoneNumberManager->updateNumberAndAttempts($phoneNumber);

        return $this->view(Response::HTTP_OK);
    }
}
