<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ZipCodeValidator extends ConstraintValidator
{
    private const PATTERNS = [
        'AD' => 'AD\\d{3}',
        'AF' => '\\d{4}',
        'AI' => 'AI-2640',
        'AL' => '\\d{4}',
        'AM' => '\\d{4}',
        'AQ' => 'BIQQ\\s1ZZ',
        'AR' => '((\\d{4})|([A-Z]{1}\\d{4}[A-Z]{3})|(\\d{4}\\s([A-Z]{1}\\d{4}[A-Z]{3})?))',
        'AS' => '\\d{5}(-\\d{4})?',
        'AT' => '\\d{4}',
        'AU' => '\\d{4}',
        'AX' => '(AX-)?\\d{5}',
        'AZ' => 'AZ\\s\\d{4}',
        'BA' => '\\d{5}',
        'BB' => 'BB\\d{5}',
        'BD' => '\\d{4}',
        'BE' => '\\d{4}',
        'BG' => '\\d{4}',
        'BH' => '\\d{3,4}',
        'BJ' => '\\d{6}',
        'BL' => '97133',
        'BM' => '[A-Z]{2}\\s((\\d{2})|([A-Z]{2}))',
        'BN' => '[A-Z]{2}\\d{4}',
        'BR' => '\\d{5}(-\\d{3})?',
        'BT' => '\\d{5}',
        'BY' => '\\d{6}',
        'CA' => '[A-CEGHJ-NPRSTV-Z]\\d[A-CEGHJ-NPRSTV-Z]\\s\\d[A-CEGHJ-NPRSTV-Z]\\d',
        'CC' => '\\d{4}',
        'CH' => '\\d{4}',
        'CL' => '\\d{3}-?\\d{4}',
        'CN' => '\\d{6}',
        'CO' => '\\d{6}',
        'CR' => '\\d{5}(-\\d{4})?',
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
        'FK' => 'FIQQ\\s1ZZ',
        'FM' => '\\d{5}(-\\d{4})?',
        'FO' => '\\d{3}',
        'FR' => '\\d{5}',
        'GB' => '[A-Z]([A-Z])?\\d(([A-Z])|(\\d))?( \\d[A-Z]{2})?',
        'GE' => '\\d{4}',
        'GF' => '973\\d{2}',
        'GG' => 'GY\\d\\d?\\s\\d[A-Z]{2}',
        'GI' => 'GX11\\s1AA',
        'GL' => '\\d{4}',
        'GN' => '\\d{3}',
        'GP' => '971\\d{2}',
        'GR' => '\\d{3}\\s\\d{2}',
        'GS' => 'SIQQ\\s1ZZ',
        'GT' => '\\d{5}',
        'GU' => '\\d{5}(-\\d{4})?',
        'GW' => '\\d{4}',
        'HK' => '\\d{5}',
        'HN' => '((\\d{5})|([A-Z]{2}\\d{4}))',
        'HR' => '\\d{5}',
        'HT' => '\\d{4}',
        'HU' => '\\d{4}',
        'IE' => '[AC-FHKNPRTV-Y]\\d{2}\\s[AC-FHKNPRTV-Y\d]{4}',
        'IC' => '\\d{5}',
        'ID' => '\\d{5}',
        'IL' => '\\d{5}(\\d{2})?',
        'IM' => 'IM\\d\\d?\\s?\\d[A-Z]{2}',
        'IN' => '\\d{3}\\s?\\d{3}',
        'IO' => 'BBND\\s1ZZ',
        'IQ' => '\\d{5}',
        'IR' => '\\d{10}',
        'IS' => '\\d{3}',
        'IT' => '\\d{5}',
        'JE' => 'JE\\d\\d?\\s\\d[A-Z]{2}',
        'JM' => '\\d{2}',
        'JO' => '\\d{5}',
        'JP' => '\\d{3}-\\d{4}',
        'KE' => '\\d{5}',
        'KH' => '\\d{5}',
        'KG' => '\\d{6}',
        'KR' => '\\d{5}',
        'KW' => '\\d{5}',
        'KY' => 'KY\\d-\\d{4}',
        'KZ' => '\\d{6}',
        'LA' => '\\d{5}',
        'LB' => '((\\d{5})|(\\d{4}\\s\\d{4}))',
        'LC' => 'LC\\d{2}\\s?\\d{3}',
        'LI' => '\\d{4}',
        'LK' => '\\d{5}',
        'LR' => '\\d{4}',
        'LS' => '\\d{3}',
        'LT' => 'LT-\\d{5}',
        'LU' => '\\d{4}',
        'LV' => 'LV-\\d{4}',
        'MA' => '\\d{5}',
        'MC' => '980\\d{2}',
        'MD' => 'MD-?\\d{4}',
        'ME' => '\\d{5}',
        'MF' => '97150',
        'MG' => '\\d{3}',
        'MH' => '\\d{5}(-\\d{4})?',
        'MK' => '\\d{4}',
        'MM' => '\\d{5}',
        'MN' => '\\d{5}',
        'MP' => '\\d{5}(-\\d{4})?',
        'MQ' => '972\\d{2}',
        'MS' => 'MSR\\s\\d{4}',
        'MT' => '[A-Z]{3}\\s\\d{4}',
        'MU' => '\\d{5}',
        'MV' => '\\d{5}',
        'MX' => '\\d{5}',
        'MY' => '\\d{5}',
        'MZ' => '\\d{4}',
        'NC' => '988\\d{2}',
        'NE' => '\\d{4}',
        'NF' => '\\d{4}',
        'NG' => '\\d{4}',
        'NI' => '\\d{5}',
        'NL' => '\\d{4}\\s[A-Z]{2}',
        'NO' => '\\d{4}',
        'NP' => '\\d{5}',
        'NZ' => '\\d{4}',
        'OM' => '\\d{3}',
        'PA' => '\\d{4}',
        'PE' => '((\\d{5})|(PE\\s\\d{4}))',
        'PF' => '987\\d{2}',
        'PG' => '\\d{3}',
        'PH' => '\\d{4}',
        'PK' => '\\d{5}',
        'PL' => '\\d{2}-\\d{3}',
        'PM' => '97500',
        'PN' => 'PCRN\\s1ZZ',
        'PR' => '\\d{5}(-\\d{4})?',
        'PS' => '\\d{3}',
        'PT' => '\\d{4}(-\\d{3})?',
        'PW' => '\\d{5}(-\\d{4})?',
        'PY' => '\\d{4}',
        'RE' => '974\\d{2}',
        'RO' => '\\d{6}',
        'RS' => '\\d{5,6}',
        'RU' => '\\d{6}',
        'SA' => '\\d{5}(-\\d{4})?',
        'SD' => '\\d{4,5}',
        'SE' => '\\d{3}\\s\\d{2}',
        'SG' => '((\\d{2})|(\\d{4})|(\\d{6}))',
        'SH' => '[A-Z]{4}\\s\\dZZ',
        'SI' => '(SI-)?\\d{4}',
        'SJ' => '\\d{4}',
        'SK' => '\\d{3}\\s\\d{2}',
        'SM' => '4789\\d',
        'SN' => '\\d{5}',
        'SO' => '[A-Z]{2}\\s\\d{5}',
        'SV' => '\\d{4}',
        'SZ' => '[A-Z]{1}\\d{3}',
        'TC' => 'TKCA\\s1ZZ',
        'TH' => '\\d{5}',
        'TJ' => '\\d{6}',
        'TM' => '\\d{6}',
        'TN' => '\\d{4}',
        'TR' => '\\d{5}',
        'TT' => '\\d{6}',
        'TW' => '\\d{3}(-?\\d{2})',
        'TZ' => '\\d{5}',
        'UA' => '\\d{5}',
        'UM' => '96898',
        'US' => '\\d{5}(-\\d{4})?',
        'UY' => '\\d{5}',
        'UZ' => '\\d{6}',
        'VA' => '00120',
        'VC' => 'VC\\d{4}',
        'VE' => '\\d{4}(-[A-Z])?',
        'VG' => 'VG\\d{4}',
        'VI' => '\\d{5}(-\\d{4})?',
        'VN' => '\\d{6}',
        'WF' => '986\\d{2}',
        'WS' => 'WS\\d{4}',
        'XK' => '\\d{5}',
        'YT' => '976\\d{2}',
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
        if (!in_array($iso, array_keys(self::PATTERNS))) {
            return;
        }

        $pattern = self::PATTERNS[$iso];

        if (!preg_match("/^{$pattern}$/", $value, $matches)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('%string%', $value)
                ->addViolation();
        }
    }
}
