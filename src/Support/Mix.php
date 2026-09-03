<?php

namespace PageBuilder\Support;

use Illuminate\Support\HtmlString;
use PageBuilder\Services\Theme;

class Mix
{
    /**
     * Get the path to a versioned Mix file.
     *
     * @param  string  $path
     * @param  string  $themeName
     * @return HtmlString|string
     *
     * @throws \Exception
     */
    public function __invoke($path, $themeName = null)
    {
        return new HtmlString(Theme::url($path, true, $themeName));
    }
}
