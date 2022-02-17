<?php

namespace ChlodAlejandro\ElectionGuard;

class Utilities {

    /**
     * Converts text from camel case to snake case.
     * @param $input string camelCaseText
     * @return string snake_case_text
     */
    public static function camelToSnakeCase(string $input): string {
        $pattern = "!([A-Z][A-Z0-9]*(?=\$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!";
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

}
