<?php

declare(strict_types=1);

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
use Illuminate\Support\Facades\View;
use PageBuilder\Services\PageRenderer;
use PageBuilder\Services\PageService;
use PageBuilder\Services\PageStorage;

class BenchmarkPage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pagebuilder:benchmark
                            {slug : The slug of the page to benchmark}
                            {--runs=10 : Number of benchmark iterations}
                            {--editor : Benchmark rendering in editor mode}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Benchmark page resolution, hydration, rendering, and memory performance for a given page slug';

    /**
     * Execute the console command.
     */
    public function handle(
        PageService $pageService,
        PageStorage $pageStorage,
        PageRenderer $pageRenderer
    ): int {
        $slug = (string) $this->argument('slug');
        $runs = max(1, (int) $this->option('runs'));
        $editor = (bool) $this->option('editor');

        $this->components->info("Benchmarking page builder for slug: [{$slug}]");

        $dbPage = $pageService->findBySlug($slug);
        $hasJson = $pageStorage->loadRaw($slug) !== null;
        $hasDb = $dbPage !== null;
        $hasView = View::exists("pages.{$slug}");

        if (! $hasJson && ! $hasDb && ! $hasView) {
            $this->components->error("Page with slug [{$slug}] not found.");

            return self::FAILURE;
        }

        [$pageData] = $pageService->resolve($slug, $dbPage);

        // Determine storage source description
        $source = $this->determineSource($slug, $pageStorage, $dbPage);

        // Count sections and blocks
        $sectionCount = count($pageData->sections());
        $blockCount = 0;
        foreach ($pageData->sections() as $sectionData) {
            if (isset($sectionData['blocks']) && is_array($sectionData['blocks'])) {
                $blockCount += count($sectionData['blocks']);
            }
        }

        // Render once to get HTML size
        $htmlOutput = $pageRenderer->renderPage($pageData, editor: $editor);
        $outputSizeBytes = strlen($htmlOutput);
        $outputSizeFormatted = $this->formatBytes($outputSizeBytes);

        // Display Info
        $this->components->twoColumnDetail('Slug', $slug);
        $this->components->twoColumnDetail('Source', $source);
        $this->components->twoColumnDetail('Sections', (string) $sectionCount);
        $this->components->twoColumnDetail('Blocks', (string) $blockCount);
        $this->components->twoColumnDetail('Editor Mode', $editor ? 'Enabled' : 'Disabled');
        $this->components->twoColumnDetail('Output HTML Size', "{$outputSizeFormatted} ({$outputSizeBytes} bytes)");
        $this->components->twoColumnDetail('Iterations (Runs)', (string) $runs);

        $this->newLine();
        $this->components->info('Running performance benchmark...');

        // Perform benchmark iterations
        $hydrationTimes = [];
        $renderTimes = [];
        $pipelineTimes = [];

        $startMemory = memory_get_usage();

        for ($i = 0; $i < $runs; $i++) {
            // Measure Hydration / Resolution
            $t0 = microtime(true);
            [$resolvedPage] = $pageService->resolve($slug, $dbPage);
            $t1 = microtime(true);

            // Measure Rendering
            $pageRenderer->renderPage($resolvedPage, editor: $editor);
            $t2 = microtime(true);

            $hydrationMs = ($t1 - $t0) * 1000;
            $renderMs = ($t2 - $t1) * 1000;
            $pipelineMs = ($t2 - $t0) * 1000;

            $hydrationTimes[] = $hydrationMs;
            $renderTimes[] = $renderMs;
            $pipelineTimes[] = $pipelineMs;
        }

        $endPeakMemory = memory_get_peak_usage();
        $memoryDiff = max(0, $endPeakMemory - $startMemory);

        // Stats calculation helper
        $calcStats = function (array $times): array {
            $count = count($times);
            $total = array_sum($times);
            $avg = $count > 0 ? $total / $count : 0;
            $min = $count > 0 ? min($times) : 0;
            $max = $count > 0 ? max($times) : 0;

            return [
                'avg' => sprintf('%.3f ms', $avg),
                'min' => sprintf('%.3f ms', $min),
                'max' => sprintf('%.3f ms', $max),
                'total' => sprintf('%.3f ms', $total),
            ];
        };

        $hydStats = $calcStats($hydrationTimes);
        $renStats = $calcStats($renderTimes);
        $pipStats = $calcStats($pipelineTimes);

        $this->table(
            ['Stage', 'Avg', 'Min', 'Max', 'Total'],
            [
                ['Hydration / Resolve', $hydStats['avg'], $hydStats['min'], $hydStats['max'], $hydStats['total']],
                ['Blade Rendering', $renStats['avg'], $renStats['min'], $renStats['max'], $renStats['total']],
                ['Full Pipeline', $pipStats['avg'], $pipStats['min'], $pipStats['max'], $pipStats['total']],
            ]
        );

        $this->components->twoColumnDetail('Peak Memory Usage', $this->formatBytes($endPeakMemory));

        $this->newLine();
        $this->components->info('Benchmark completed successfully.');

        return self::SUCCESS;
    }

    /**
     * Determine where the page data was loaded from.
     */
    private function determineSource(string $slug, PageStorage $pageStorage, mixed $dbPage): string
    {
        if ($pageStorage->loadRaw($slug) !== null) {
            return "JSON File (pages/{$slug}.json)";
        }

        if ($dbPage !== null) {
            $template = $dbPage->template ?: 'page';

            return "Database Record (ID: {$dbPage->id}, Template: {$template})";
        }

        return 'Default Fallback Template (templates/page.json)';
    }

    /**
     * Format byte sizes into human readable strings.
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return "{$bytes} B";
        }

        if ($bytes < 1048576) {
            return sprintf('%.2f KB', $bytes / 1024);
        }

        return sprintf('%.2f MB', $bytes / 1048576);
    }
}
