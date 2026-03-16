<?php

namespace Workbench\App\Models;

use Coderstm\PageBuilder\Models\Page as Model;
use Workbench\Database\Factories\PageFactroy;

class Page extends Model
{
    /**
     * Create a new factory instance for the model.
     *
     * @return PageFactroy
     */
    protected static function newFactory()
    {
        return PageFactroy::new();
    }
}
