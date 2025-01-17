<?php declare(strict_types = 1);

namespace App\Utils\Fetcher;

use App\Entity\Profile;
use RuntimeException;

interface ProfileFetcherInterface
{
    /**
     * Returns the Profile for the current User.
     *
     * @return Profile
     * @throws RuntimeException
     */
    public function fetchProfile(): ?Profile;
}
