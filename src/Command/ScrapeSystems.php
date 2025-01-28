<?php

namespace App\Command;

use App\Repository\ScraperRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('app:scrape-systems')]
class ScrapeSystems extends Command
{
    public function __construct(private ScraperRepositoryInterface $scraperRepository)
    {}

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $scrapers = $this->scraperRepository->findAll();

        foreach ($scrapers as $scraper) {
            $scraper->scrape();
        }

        return Command::SUCCESS;
    }
}