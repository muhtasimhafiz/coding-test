<?php

namespace App\Scraper\GalariaSpa;

use App\Scraper\core\AbstractScraper;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

class GalariaSpaScrapper extends AbstractScraper
{
  protected $url = "https://www.galleriaspasalon.com";
  private $output;

  public function __construct(HttpClientInterface $client)
  {
    parent::__construct($client);
    $this->output = new ConsoleOutput();
  }

  public function scrape(): array
  {

    $this->output->writeln(messages: "Galleria Spa Salon scraper started...");

    $response = $this->client->request('GET', $this->url);
    $crawler = new Crawler($response->getContent());
    $categories = $this->extractCategories($crawler);
    $allServices = [];

    foreach ($categories as $category) {
      try {
        $this->output->writeln("Visiting category: " . $category['name']);
        $categoryResponse = $this->client->request('GET', $this->url . $category['url']);
        $categoryCrawler = new Crawler($categoryResponse->getContent());
        $services = $this->extractServices($categoryCrawler, $category['name']);
        $allServices[$category['name']] =  $services;

        sleep(4);
      } catch (\Exception $e) {
      }
    }

    $this->writeToCsv($allServices);
    return [
      'services' => $allServices,
      "name" => "Galleria Spa Salon",
    ];
  }

  protected function extractServices($crawler): array
  {
    $services = [];


    try {
      $crawler->filter('.sqs-html-content')->each(function ($node) use (&$services) {
        // Extract the title/price line
        $titlePriceText = $node->filter('h3')->text('');
        $this->output->writeln("Extracted: " . $titlePriceText);

        // Extract description
        $description = $node->filter('p')->text('');

        // Parse the title/price line
        if (preg_match('/^(.*?)\s*\|\s*\$(\d+)\s*\((\d+)\s*Minutes\)$/', $titlePriceText, $matches)) {
          $services[] = [
            'name' => trim($matches[1]),
            'price' => (float) $matches[2],
            'duration' => (int) $matches[3],
            'description' => trim($description)
          ];
        }
      });
    } catch (\Exception $e) {
      $this->output->writeln($e->getMessage());
      error_log("Error extracting services: " . $e->getMessage());
    }

    return $services;
  }

  private function extractCategories(Crawler $crawler): array
  {
    $categories = [];
    $crawler->filter('.folder')->each(function ($folderNode) use (&$categories) {
      $folderToggle = $folderNode->filter('.folder-toggle');
      if ($folderToggle->count() > 0 && trim($folderToggle->text('')) === 'Services') {
        $folderNode->filter('.collection a')->each(function ($linkNode) use (&$categories) {
          $categories[] = [
            'name' => trim($linkNode->text('')),
            'url' => $linkNode->attr('href')
          ];
        });
      }
    });
    return $categories;
  }

  private function writeToCsv(array $allServices): void
  {
    $this->output->writeln("Writing data to CSV...");

    $filename = 'GaleriaSpa.csv';
    $file = fopen($filename, 'w');

    fputcsv($file, ['Category', 'Name', 'Price', 'Duration', 'Description']);

    foreach ($allServices as $category => $services) {
      foreach ($services as $service) {
        fputcsv($file, [
          'category' => $category,
          'name' => $service['name'],
          'price' => $service['price'],
          'duration' => $service['duration'],
          'description' => $service['description']
        ]);
      }
    }

    fclose($file);
    $this->output->writeln("Data written to " . $filename);
  }
}
