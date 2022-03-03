<?php

namespace ChlodAlejandro\ElectionGuard\Schema\Manifest;

class SerializableUtils {

    /**
     * Serializes an array of serializable objects.
     * @param \ChlodAlejandro\ElectionGuard\Schema\ISerializable[]|null $arr
     * @return array[]|null
     */
    public static function serializeArray(?array $arr): ?array {
        if (is_null($arr))
            return null;

        return array_map(function ($value) {
            return $value->serialize();
        }, $arr);
    }

    /**
     * @param callable $inflator
     * @param \ChlodAlejandro\ElectionGuard\Schema\IDeserializable[]|null $arr
     * @return array|null
     */
    public static function deserializeArray(callable $inflator, ?array $arr): ?array {
        if (is_null($arr))
            return null;

        $final = [];
        foreach ($arr as $key => $value) {
            $final[$key] = $inflator($value);
        }
        return $final;
    }

}
