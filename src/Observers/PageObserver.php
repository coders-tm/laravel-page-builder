<?php

declare(strict_types=1);

namespace PageBuilder\Observers;

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
    public function saved(mixed $page): void
    {
        Artisan::call('pages:regenerate');
    }

    /**
     * Handle the "deleted" event.
     */
    public function deleted(mixed $page): void
    {
        Artisan::call('pages:regenerate');
    }
}
