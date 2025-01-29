<?php

namespace App\Command;

use App\Repository\ScraperRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand('app:scrape-systems')]
class ScrapeSystems extends Command
{
    public function __construct(
        private ScraperRepositoryInterface $scraperRepository,
        private HttpClientInterface $httpClient
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(messages: "running scrapers...");

        $scrapers = $this->scraperRepository->findAll();
        foreach ($scrapers as $scraper) {
            $result = $scraper->scrape();
        }

        return Command::SUCCESS;
    }
}
