<?php

declare(strict_types=1);

namespace Workbench\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

use function Orchestra\Testbench\workbench_path;

class SetupThemeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'workbench:setup-theme';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup theme static copy for workbench';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->components->info('Setting up workbench theme...');

        $destination = base_path('themes/default');
        File::ensureDirectoryExists($destination, 0755, true);

        $source = workbench_path('resources');
        if (File::isDirectory($source)) {
            File::copyDirectory($source, $destination);
        }

        $homeJson = resource_path('views/pages/home.json');
        if (File::exists($homeJson)) {
            File::copy($homeJson, $destination.'/views/pages/home.json');
        }

        $settingsJson = workbench_path('settings.json');
        if (File::exists($settingsJson)) {
            File::copy($settingsJson, resource_path('settings.json'));
        }

        $this->components->info('Workbench theme set up successfully.');

        return self::SUCCESS;
    }
}
