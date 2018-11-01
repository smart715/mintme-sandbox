<?php

namespace App\Fetcher;

interface OrdersUserFetcherInterface
{
    public function fetchMakerIds(): array;

    public function fetchTakerIds(): array;

    public function fetchAllIds(): array;
}
