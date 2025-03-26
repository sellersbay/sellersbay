<?php

namespace App\Command;

use App\Service\SeoStatusService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-seo-status',
    description: 'Update SEO status for all WooCommerce products',
)]
class UpdateSeoStatusCommand extends Command
{
    private SeoStatusService $seoStatusService;

    public function __construct(SeoStatusService $seoStatusService)
    {
        $this->seoStatusService = $seoStatusService;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Updating SEO status for all WooCommerce products');

        try {
            $io->section('Starting update process...');
            $count = $this->seoStatusService->updateAllProductsSeoStatus();
            
            $io->success("Successfully updated SEO status for {$count} products.");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('An error occurred: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
} 