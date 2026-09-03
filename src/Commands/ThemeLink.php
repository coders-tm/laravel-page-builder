<?php

namespace PageBuilder\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use PageBuilder\Services\Theme;

class ThemeLink extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'theme:link
                            {--force : Overwrite the assets symlink if it already exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Link the theme, ensuring assets are correctly linked.';

    /**
     * Force flag
     *
     * @var bool
     */
    protected $force = false;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $themes = base_path('themes');

        // ensure public/themes directory exists
        File::ensureDirectoryExists(public_path('themes'));

        // for each theme create a symbolic link to the public directory
        foreach (File::directories($themes) as $theme) {
            $name = basename($theme);
            $mixPath = Theme::mixPath($name);
            $source = base_path("themes/{$name}/assets");
            $destination = public_path(ltrim($mixPath, '/'));

            // ensure parent directory of destination exists
            File::ensureDirectoryExists(dirname($destination));

            // create symlink for the theme assets
            if (! File::exists($destination) || $this->force) {
                if (File::exists($destination)) {
                    File::delete($destination);
                }
                symlink($source, $destination);
                $this->info("Linked theme assets: {$name} -> {$mixPath}");
            }
        }
    }
}
