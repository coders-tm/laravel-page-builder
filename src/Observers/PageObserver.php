<?php declare(strict_types=1);

/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PageBuilder\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;

/**
 * Observer for the Page model that regenerates the page registry
 * cache whenever a page is created, updated, or deleted.
 */
class PageObserver
{
    /**
     * Handle the "saved" event (covers both created and updated).
     */
    public function saved(Model $page): void
    {
        $this->regenerate();
    }

    /**
     * Handle the "deleted" event.
     */
    public function deleted(Model $page): void
    {
        $this->regenerate();
    }

    private function regenerate(): void
    {
        Artisan::call('pages:regenerate');
    }
}
