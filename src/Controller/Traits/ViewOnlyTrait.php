<?php declare(strict_types = 1);

namespace App\Controller\Traits;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

trait ViewOnlyTrait
{
    protected SessionInterface $session;

    protected function isViewOnly(): bool
    {
        return $this->session->get('view_only_mode', false);
    }
}
