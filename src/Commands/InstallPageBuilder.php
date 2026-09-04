<?php declare(strict_types=1);

/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PageBuilder\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallPageBuilder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pagebuilder:install
                            {--force : Overwrite existing files}
                            {--migrate : Run database migrations after publishing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the Page Builder package: publish config, migrations, assets, and scaffold default theme views.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $force = (bool) $this->option('force');

        $this->components->info('Installing Page Builder...');

        $this->publishConfig($force);
        $this->publishAssets($force);
        $this->scaffoldThemeViews($force);

        if ($this->option('migrate')) {
            $this->components->info('Running migrations...');
            $this->call('migrate');
        }

        $this->newLine();
        $this->components->info('Page Builder installed successfully.');

        return self::SUCCESS;
    }

    /**
     * Publish the package config file.
     */
    private function publishConfig(bool $force): void
    {
        $this->components->task('Publishing config', function () use ($force) {
            $this->callSilently('vendor:publish', [
                '--tag' => 'pagebuilder-config',
                '--force' => $force,
            ]);
        });
    }

    /**
     * Publish the compiled frontend assets.
     */
    private function publishAssets(bool $force): void
    {
        $this->components->task('Publishing assets', function () use ($force) {
            $this->callSilently('vendor:publish', [
                '--tag' => 'pagebuilder-assets',
                '--force' => $force,
            ]);
            $this->callSilently('vendor:publish', [
                '--tag' => 'pagebuilder-statics',
                '--force' => $force,
            ]);
        });
    }

    /**
     * Copy stub theme views (layouts, sections, blocks, pages) into the host application.
     */
    private function scaffoldThemeViews(bool $force): void
    {
        $stubsPath = __DIR__.'/../../stubs';

        $targets = [
            'layouts' => resource_path('views/layouts'),
            'sections' => config('pagebuilder.sections', resource_path('views/sections')),
            'blocks' => config('pagebuilder.blocks', resource_path('views/blocks')),
            'pages' => config('pagebuilder.pages', resource_path('views/pages')),
            'templates' => config('pagebuilder.templates', resource_path('views/templates')),
        ];

        foreach ($targets as $dir => $destination) {
            $source = $stubsPath.'/'.$dir;

            if (! File::isDirectory($source)) {
                continue;
            }

            $this->components->task("Scaffolding {$dir}", function () use ($source, $destination, $force) {
                File::ensureDirectoryExists($destination);

                foreach (File::files($source) as $file) {
                    $target = $destination.'/'.$file->getFilename();

                    if (File::exists($target) && ! $force) {
                        continue;
                    }

                    File::copy($file->getPathname(), $target);
                }
            });
        }
    }
}
