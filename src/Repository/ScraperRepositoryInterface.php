<?php

namespace App\Repository;

use App\ScraperInterface;

interface ScraperRepositoryInterface
{
    /** @return ScraperInterface[] */
    public function findAll(): array;
}