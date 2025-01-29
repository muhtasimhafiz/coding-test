<?php

namespace App\Scraper\core;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\HttpClient\HttpClientInterface;


abstract class AbstractScraper implements ScraperInterface
{
  protected $client;
  protected $parser;



  public function __construct(HttpClientInterface $client)
  {
    $this->client = $client;
  }

  abstract protected function extractServices(Crawler $crawler): array;
}
