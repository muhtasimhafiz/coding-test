<?php

namespace App\Repository;

use App\Scraper\HairByJuls\HairByJulsScrapper;
use App\Scraper\GalariaSpa\GalariaSpaScrapper;
use Symfony\Contracts\HttpClient\HttpClientInterface;


class ScraperRepository implements ScraperRepositoryInterface
{
  private $httpClient;
  public function __construct(HttpClientInterface $httpClient)
  {
    $this->httpClient = $httpClient;
  }

  public function findAll(): array
  {
    // Implementation
    return [
      "GaleriaSpa" => new GalariaSpaScrapper($this->httpClient),
      "HairByJuls" => new HairByJulsScrapper($this->httpClient),
    ];
  }
}
