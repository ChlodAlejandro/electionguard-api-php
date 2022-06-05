<?php

namespace ChlodAlejandro\ElectionGuard;

class Utilities {

    /**
     * Makes text safe to put in an ID.
     * @param $input string camelCaseText
     * @return string snake_case_text
     */
    public static function idSafe(string $input): string {
        $hash = sha1($input);

        return preg_replace('/[^a-z\d_\-]/i', '_', $input)
            . "-" . substr($hash, 0, 6);
    }

    /**
     * Checks if a value can be included in the election manifest.
     * @param $value
     * @return string
     */
    public static function filter($value): string {
        return isset($value);
    }

    /**
     * Convert a URL parsed with `parse_url` to a string.
     *
     * @param $parsed_url array The parsed URL
     * @return string The resultant string
     */
    public static function unparse_url(array $parsed_url): string {
        $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
        $host     = $parsed_url['host'] ?? '';
        $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
        $user     = $parsed_url['user'] ?? '';
        $pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : '';
        $pass     = ($user || $pass) ? "$pass@" : '';
        $path     = $parsed_url['path'] ?? '';
        $query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
        $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
        return "$scheme$user$pass$host$port$path$query$fragment";
    }

}
