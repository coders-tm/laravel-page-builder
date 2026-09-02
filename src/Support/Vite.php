<?php

namespace PageBuilder\Support;

use PageBuilder\Services\Theme;

class Vite extends \Illuminate\Foundation\Vite
{
    protected $theme;

    public function __invoke($entrypoints, $theme = null)
    {
        $this->theme = $theme ?: Theme::active();
        $buildDirectory = ltrim(Theme::mixPath($this->theme), '/').'/build';
        $this->hotFile = $buildDirectory.'/hot';

        return parent::__invoke($entrypoints, $buildDirectory);
    }
}
