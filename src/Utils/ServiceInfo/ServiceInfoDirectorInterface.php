<?php declare(strict_types = 1);

namespace App\Utils\ServiceInfo;

use App\Utils\ServiceInfo\Model\ServiceInfo;

interface ServiceInfoDirectorInterface
{
    public function build(): ServiceInfo;
}
