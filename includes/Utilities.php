<?php

namespace ChlodAlejandro\ElectionGuard;

class Utilities {

    /**
     * Converts text from camel case to snake case.
     * @param $input string camelCaseText
     * @return string snake_case_text
     */
    public static function camelToSnakeCase(string $input): string {
        $pattern = "!([A-Z][A-Z\d]*(?=\$|[A-Z][a-z\d])|[A-Za-z][a-z\d]+)!";
        preg_match_all($pattern, $input, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ?
                strtolower($match) :
                lcfirst($match);
        }

        return implode('_', $ret);
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
