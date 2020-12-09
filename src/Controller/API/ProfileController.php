<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Exception\ApiBadRequestException;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Sirprize\PostalCodeValidator\Validator as PostalCodeValidator;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Rest\Route("/api/profile")
 */
class ProfileController extends AbstractFOSRestController
{
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
}
