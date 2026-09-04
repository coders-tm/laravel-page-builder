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
