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

namespace PageBuilder\Registry;

use Illuminate\Support\Facades\File;

/**
 * Extracts @schema() definitions from section Blade files.
 *
 * The @schema() directive in a Blade file contains a PHP array expression
 * defining the section's schema (name, settings, blocks, presets).
 * This class parses it using balanced bracket matching.
 */
class SchemaExtractor
{
    /**
     * Extract @schema() definition from a Blade file.
     *
     * @param  string  $filePath  Absolute path to the Blade file.
     * @return array|null Parsed schema array or null if not found.
     */
    public function extract(string $filePath): ?array
    {
        if (! File::exists($filePath)) {
            return null;
        }

        $content = File::get($filePath);

        return $this->parseSchema($content);
    }

    /**
     * Parse the @schema() directive from Blade content.
     */
    protected function parseSchema(string $content): ?array
    {
        $marker = '@schema(';
        $startPos = strpos($content, $marker);

        if ($startPos === false) {
            return null;
        }

        $arrayStart = $startPos + strlen($marker);
        $arrayContent = $this->extractBalancedExpression($content, $arrayStart);

        if ($arrayContent === null) {
            return null;
        }

        try {
            $schema = eval("return {$arrayContent};");

            if (is_array($schema)) {
                return $schema;
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }

    /**
     * Extract a balanced bracket expression from a string.
     */
    protected function extractBalancedExpression(string $content, int $start): ?string
    {
        $pos = $start;
        $length = strlen($content);

        while ($pos < $length && ctype_space($content[$pos])) {
            $pos++;
        }

        if ($pos >= $length || $content[$pos] !== '[') {
            return null;
        }

        $depth = 0;
        $inString = false;
        $stringChar = null;
        $escaped = false;
        $expressionStart = $pos;

        for ($i = $pos; $i < $length; $i++) {
            $char = $content[$i];

            if ($escaped) {
                $escaped = false;

                continue;
            }

            if ($char === '\\') {
                $escaped = true;

                continue;
            }

            if ($inString) {
                if ($char === $stringChar) {
                    $inString = false;
                    $stringChar = null;
                }

                continue;
            }

            if ($char === "'" || $char === '"') {
                $inString = true;
                $stringChar = $char;

                continue;
            }

            if ($char === '[') {
                $depth++;
            } elseif ($char === ']') {
                $depth--;

                if ($depth === 0) {
                    return substr($content, $expressionStart, $i - $expressionStart + 1);
                }
            }
        }

        return null;
    }
}
