<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ZipCodeValidator extends ConstraintValidator
{

    /** @var strings[] */
    private $patterns = [
        'AD' => '[A-Z]{2}\\d{3}',
        'AF' => '\\d{4}',
        'AI' => '[A-Z]{2}-\\d{4}',
        'AL' => '\\d{4}',
        'AM' => '\\d{4}',
        'AQ' => '[A-Z]{4}\\s?\\d[A-Z]{2}',
        'AR' => '((\\d{4})|([A-Z]{1}\\d{4}[A-Z]{3})|(\\d{4}\\s([A-Z]{1}\\d{4}[A-Z]{3})?))',
        'AS' => '\\d{5}(-\\d{4})?',
        'AT' => '\\d{4}',
        'AU' => '\\d{4}',
        'AX' => '([A-Z]{2}-)?\\d{5}',
        'AZ' => '([A-Z]{2}(\\s)?)?\\d{4}',
        'BA' => '\\d{5}',
        'BB' => '[A-Z]{2}\\d{5}',
        'BD' => '\\d{4}',
        'BE' => '\\d{4}',
        'BG' => '\\d{4}',
        'BH' => '\\d{3,4}',
        'BJ' => '\\d{6}',
        'BL' => '\\d{5}',
        'BM' => '[A-Z]{2}\\s\\d{2}',
        'BN' => '[A-Z]{2}\\d{4}',
        'BR' => '\\d{5}(-\\d{3})?',
        'BT' => '\\d{5}',
        'BY' => '\\d{6}',
        'CA' => '[A-Z]{1}\\d{1}[A-Z]{1}\\s?\\d{1}[A-Z]{1}\\d{1}',
        'CC' => '\\d{4}',
        'CH' => '\\d{4}',
        'CL' => '\\d{3}-?\\d{4}',
        'CN' => '\\d{6}',
        'CO' => '\\d{6}',
        'CR' => '((\\d{4,5})|(\\d{5}-\\d{4}))',
        'CS' => '\\d{5}',
        'CU' => '\\d{5}',
        'CV' => '\\d{4}',
        'CX' => '\\d{4}',
        'CY' => '\\d{4}',
        'CZ' => '\\d{3}\\s?\\d{2}',
        'DE' => '\\d{5}',
        'DK' => '\\d{4}',
        'DO' => '\\d{5}',
        'DZ' => '\\d{5}',
        'EC' => '\\d{6}',
        'EE' => '\\d{5}',
        'EG' => '\\d{5}',
        'ES' => '\\d{5}',
        'ET' => '\\d{4}',
        'FI' => '\\d{5}',
        'FK' => '[A-Z]{4}\\s?\\d[A-Z]{2}',
        'FM' => '\\d{5}(-\\d{4})?',
        'FO' => '([A-Z]{2}-)?(\\d{3})',
        'FR' => '((0[1-9])|([1-8]\\d)|(9[0-8])|(2A)|(2B))\\d{3}',
        'GB' => '[A-Z]([A-Z])?\\d(([A-Z])|(\\d))?( \\d[A-Z]{2})',
        'GE' => '\\d{4}',
        'GF' => '\\d{5}',
        'GG' => '[A-Z]{2}\\d\\d?\\s?\\d[A-Z]{2}',
        'GI' => '[A-Z]{2}\\d{2}\\s?\\d[A-Z]{2}',
        'GL' => '\\d{4}',
        'GN' => '\\d{3}',
        'GP' => '\\d{5}',
        'GR' => '\\d{3}\\s?\\d{2}',
        'GS' => '[A-Z]{4}\\s?\\d[A-Z]{2}',
        'GT' => '\\d{5}',
        'GU' => '\\d{5}(-\\d{4})?',
        'GW' => '\\d{4}',
        'HK' => '\\d{5}',
        'HN' => '((\\d{5})|([A-Z]{2}\\d{4}))',
        'HR' => '\\d{5}',
        'HT' => '\\d{4}',
        'HU' => '\\d{4}',
        'IE' => '(([A-Z]{2}(\\s(([A-Z0-9]{1})|(\\d{2})))?)|([A-Z]{3}))',
        'IC' => '\\d{5}',
        'ID' => '\\d{5}',
        'IL' => '\\d{5}(\\d{2})?',
        'IM' => '[A-Z]{2}\\d\\d?\\s?\\d[A-Z]{2}',
        'IN' => '\\d{3}\\s?\\d{3}',
        'IO' => '[A-Z]{4}\\s?\\d[A-Z]{2}',
        'IQ' => '\\d{5}',
        'IR' => '\\d{10}',
        'IS' => '\\d{3}',
        'IT' => '\\d{5}',
        'JE' => '[A-Z]{2}\\d\\d?\\s?\\d[A-Z]{2}',
        'JM' => '\\d{2}',
        'JO' => '\\d{5}',
        'Jp' => '\\d{3}-?\\d{4}',
        'KE' => '\\d{5}',
        'KH' => '\\d{5}',
        'KG' => '\\d{6}',
        'KR' => '\\d{5}',
        'KW' => '\\d{5}',
        'KY' => '[A-Z]{2}\\d-\\d{4}',
        'KZ' => '\\d{6}',
        'LA' => '\\d{5}',
        'LB' => '((\\d{5})|(\\d{4}\\s\\d{4}))',
        'LC' => '[A-Z]{2}\\d{2}\\s?\\d{3}',
        'LI' => '\\d{4}',
        'LK' => '\\d{5}',
        'LR' => '\\d{4}',
        'LS' => '\\d{3}',
        'LT' => '([A-Z]{2}-)?(\\d{5})',
        'LU' => '\\d{4}',
        'LV' => '([A-Z]{2}-)?(\\d{4})',
        'MA' => '\\d{5}',
        'MC' => '\\d{5}',
        'MD' => '([A-Z]{2}(-)?)?\\d{4}',
        'ME' => '\\d{5}',
        'MF' => '\\d{5}',
        'MG' => '\\d{3}',
        'MH' => '\\d{5}(-\\d{4})?',
        'MK' => '\\d{4}',
        'MM' => '\\d{5}',
        'MN' => '\\d{5,6}',
        'MP' => '\\d{5}(-\\d{4})?',
        'MQ' => '\\d{5}',
        'MT' => '[A-Z]{3}\\s\\d{2,4}',
        'MU' => '\\d{5}',
        'MV' => '\\d{4,5}',
        'MX' => '\\d{5}',
        'MY' => '\\d{5}',
        'MZ' => '\\d{4}',
        'NC' => '\\d{5}',
        'NE' => '\\d{4}',
        'NF' => '\\d{4}',
        'NG' => '\\d{4}',
        'NI' => '\\d{5}',
        'NL' => '(\\d{4})\\s?[A-Z]{2}',
        'NO' => '\\d{4}',
        'NP' => '\\d{5}',
        'NZ' => '\\d{4}',
        'OM' => '\\d{3}',
        'PA' => '\\d{4}',
        'PE' => '((\\d{5})|([A-Z]{2}\\s\\d{4}))',
        'PF' => '\\d{5}',
        'PG' => '\\d{3}',
        'PH' => '\\d{4}',
        'PK' => '\\d{5}',
        'PL' => '\\d{2}-?\\d{3}',
        'PM' => '\\d{5}',
        'PN' => '[A-Z]{4}\\s?\\d[A-Z]{2}',
        'PR' => '\\d{5}(-\\d{4})?',
        'PS' => '\\d{3}',
        'PT' => '\\d{4}(-\\d{3})?',
        'PW' => '\\d{5}(-\\d{4})?',
        'PY' => '\\d{4}',
        'RE' => '\\d{5}',
        'RO' => '\\d{6}',
        'RS' => '\\d{5,6}',
        'RU' => '\\d{6}',
        'SA' => '\\d{5}(-\\d{4})?',
        'SD' => '\\d{4,5}',
        'SE' => '\\d{3}\\s?\\d{2}',
        'SG' => '\\d{2,6}',
        'SH' => '[A-Z]{4}\\s?\\d[A-Z]{2}',
        'SI' => '([A-Z]{2}-)?\\d{4}',
        'SJ' => '\\d{4}',
        'SK' => '\\d{3}\\s?\\d{2}',
        'SM' => '\\d{5}',
        'SN' => '\\d{5}',
        'SO' => '[A-Z]{2}\\s?\\d{5}',
        'SV' => '\\d{4}',
        'SZ' => '[A-Z]{1}\\d{3}',
        'TC' => '[A-Z]{4}\\s?\\d[A-Z]{2}',
        'TH' => '\\d{5}',
        'TJ' => '\\d{6}',
        'TM' => '\\d{6}',
        'TN' => '\\d{4}',
        'TR' => '\\d{5}',
        'TT' => '\\d{6}',
        'TW' => '\\d{3}((-)?\\d{2})',
        'TZ' => '\\d{5}',
        'UA' => '\\d{5}',
        'UM' => '\\d{5}',
        'US' => '\\d{5}(-\\d{4})?',
        'UY' => '\\d{5}',
        'UZ' => '\\d{6}',
        'VA' => '\\d{5}',
        'VC' => '[A-Z]{2}\\d{4}',
        'VE' => '\\d{4}(-[A-Z])?',
        'VG' => '[A-Z]{2}\\d{4}',
        'VI' => '\\d{5}(-\\d{4})?',
        'VN' => '\\d{5,6}',
        'WF' => '\\d{5}',
        'WS' => '[A-Z]{2}\\d{4}',
        'XK' => '\\d{5}',
        'YT' => '\\d{5}',
        'YU' => '\\d{5}',
        'ZA' => '\\d{4}',
        'ZM' => '\\d{5}',
    ];

    /** {@inheritdoc} */
    public function validate($value, Constraint $constraint)
    {
        $value = strtoupper($value ?? '');

        if (!$constraint instanceof ZipCode) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\ZipCode');
        }

        // if iso code is not specified, try to fetch it via getter from the object, which is currently validated
        if (null === $constraint->iso) {
            $object = $this->context->getObject();
            $getter = $constraint->getter;

            if (!is_callable([$object, $getter])) {
                $objectClass = get_class($object);

                throw new ConstraintDefinitionException(
                    "Method '{$getter}' used as iso code getter does not exist in class '{$objectClass}'"
                );
            }

            $iso = $object->$getter();
        } else {
            $iso = $constraint->iso;
        }

        // ignore empty iso
        if (empty($iso)) {
            return;
        }

        // ignore if iso does not have codes
        if (!in_array($iso, array_keys($this->patterns))) {
            return;
        }

        $pattern = $this->patterns[$iso];

        if (!preg_match("/^{$pattern}$/", $value, $matches)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('%string%', $value)
                ->addViolation();
        }
    }
}
