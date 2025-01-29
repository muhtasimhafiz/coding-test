<?php

namespace App\Scraper\HairByJuls;

use App\Scraper\core\AbstractScraper;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Panther\Client;
use Symfony\Contracts\HttpClient\HttpClientInterface;


class HairByJulsScrapper extends AbstractScraper
{
  protected $url = "https://www.hairbyjuls.com/";


  private $output;

  public function __construct(HttpClientInterface $client)
  {
      parent::__construct($client);
      $this->output = new ConsoleOutput();
  }

  public function scrape(): array
  {
    

    $this->output->writeln("Starting HairByJuls scraper...");

    try {
      $pantherClient = Client::createChromeClient();

      $this->output->writeln("Navigating to services page...");
      $crawler = $pantherClient->request('GET', "https://book.squareup.com/appointments/suup0ryzi55r8e/location/76CPF1HSAPHN7/services");

      $this->output->writeln("Waiting for services to load...");
      $pantherClient->waitFor('.service-row');

      sleep(2);

      $services = $this->extractServices($crawler);

      $this->writeToCsv($services);

      $this->output->writeln(sprintf("Found %d services", count($services)));

      $pantherClient->quit();

      $data =  [
        'services' => $services,
        'name' => 'Hair By Juls',
      ];

      return $data;
    } catch (\Exception $e) {
      $this->output->writeln("<error>Scraping error: " . $e->getMessage() . "</error>");
      return [
        'services' => [],
        'name' => 'Hair By Juls',
      ];
    }
  }


  private function parsePriceAndDuration(string $text): array
  {
    $result = [
      'price' => '',
      'duration' => ''
    ];

    $text = strtolower(trim($text)); // Normalize the text

    if (preg_match('/(\d+)\s*hr\s*(?:(\d+)\s*min)?/', $text, $durationMatches)) {
      $hours = (int)$durationMatches[1];
      $minutes = isset($durationMatches[2]) ? (int)$durationMatches[2] : 0;

      if ($minutes > 0) {
        $result['duration'] = "{$hours}hr {$minutes}min";
      } else {
        $result['duration'] = "{$hours}hr";
      }
    } elseif (preg_match('/(\d+)\s*min/', $text, $durationMatches)) {
      $result['duration'] = "{$durationMatches[1]}min";
    }

    if (preg_match('/from\s*\$?([\d,.]+)/', $text, $matches)) {
      $amount = number_format((float)str_replace(',', '', $matches[1]), 2);
      $result['price'] = "from \${$amount}";
    } elseif (preg_match('/\$?([\d,.]+)/', $text, $matches)) {
      $amount = number_format((float)str_replace(',', '', $matches[1]), 2);
      $result['price'] = "\${$amount}";
    }

    return $result;
  }

  protected function extractServices($crawler): array
  {
    $services = [];

    

    try {
      $serviceElements = $crawler->filter('.service-row');

      $this->output->writeln("Found " . $serviceElements->count() . " service elements");

      $serviceElements->each(function ($node) use (&$services, &$output) {
        try {
          $name = $node->filter('label[slot="label"]')->text();
          $this->output->writeln("Processing service: " . $name);

          $subtextDiv = $node->filter('div[slot="subtext"]');
          $paragraphs = $subtextDiv->filter('p');

          $description = $paragraphs->first()->text();
          $priceAndDuration = $paragraphs->last()->text();

          $priceInfo = $this->parsePriceAndDuration($priceAndDuration);

          $service = [
            'name' => trim($name),
            'description' => trim($description),
            'price' => $priceInfo['price'],
            'duration' => $priceInfo['duration'],
            'raw_price_text' => $priceAndDuration
          ];

          $services[] = $service;
        } catch (\Exception $e) {
          $this->output->writeln("<error>Error processing individual service: " . $e->getMessage() . "</error>");
        }
      });
    } catch (\Exception $e) {
      $this->output->writeln("<error>Error in service extraction: " . $e->getMessage() . "</error>");
    }

    return $services;
  }


  private function writeToCsv(array $services): void
  {
    
    $this->output->writeln("Writing data to CSV...");

    $filename = 'hairbyjuls.csv';
    $file = fopen($filename, 'w');

    fputcsv($file, ['Name', 'Description', 'Price', 'Duration', 'Raw Price Text']);

    foreach ($services as $service) {
      fputcsv($file, $service);
    }

    fclose($file);
    $this->output->writeln("Data written to " . $filename);
  }
}
