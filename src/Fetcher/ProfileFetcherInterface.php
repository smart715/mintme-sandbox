<?php

namespace App\Fetcher;

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
