<?php

namespace App\Helpers;

/** This class contains a set of handy functions to work with arrays */
class ArrayHelper {
    /**
     * This function performs json_encode with the flags needed
     * @param mixed $object An object
     * @return ?string JSON representation
     */
    public static function toJSON(mixed $object): ?string {
        $flags = JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE | JSON_PRETTY_PRINT;

        return json_encode($object, $flags) ?: null;
    }

    /**
     * This function returns an associative array made from the plain array provided
     * @param callable $callback The function which modifies the array values
     * @param string[] $arr Source array
     * @return array[string]null
     */
    public static function toHashmap(callable $callback, array $arr): array {
        $map = [];

        foreach ($arr as $val) {
            $map[$callback($val)] = null;
        }

        return $map;
    }
}