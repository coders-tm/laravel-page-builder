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
