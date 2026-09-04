<?php declare(strict_types=1);

/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PageBuilder\Schema;

/**
 * Defines the available setting field types for section and block schemas.
 *
 * Each case maps to its string identifier used in Blade @schema definitions.
 */
enum SettingType: string
{
    case Text = 'text';
    case Textarea = 'textarea';
    case Image = 'image';
    case ImagePicker = 'image_picker';
    case Select = 'select';
    case Checkbox = 'checkbox';
    case Radio = 'radio';
    case Color = 'color';
    case Number = 'number';
    case Url = 'url';
    case Wysiwyg = 'wysiwyg';
    case Repeater = 'repeater';
    case PageLink = 'page_link';
}
