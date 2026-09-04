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
