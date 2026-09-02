<?php

namespace PageBuilder\Commands;

use PageBuilder\Services\PageRegistry;
use Illuminate\Console\Command;

class RegeneratePages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pages:regenerate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Flush and rebuild the page registry cache from the database';

    /**
     * Execute the console command.
     */
    public function handle(PageRegistry $registry): int
    {
        $this->info('Rebuilding page registry cache...');

        $registry->reload();

        $count = count($registry->pages());

        if ($count === 0) {
            $this->warn('No active pages found in database.');
        }

        $this->info("Page registry rebuilt with {$count} pages.");

        return Command::SUCCESS;
    }
}
