<?php declare(strict_types = 1);

namespace App\Services\TranslatorService;

use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface as BaseTranslatorInterface;

interface TranslatorInterface extends BaseTranslatorInterface, LocaleAwareInterface
{

}
