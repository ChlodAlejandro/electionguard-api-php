<?php

namespace ChlodAlejandro\ElectionGuard\Schema\Manifest;

use ChlodAlejandro\ElectionGuard\Schema\ISerializable;

class SerializableUtils {

    /**
     * Serializes an array of serializable objects.
     * @param ISerializable[]|null $arr
     * @return array[]|null
     */
    public static function serializeArray(?array $arr): ?array {
        if (is_null($arr))
            return null;

        return array_map(function ($value) {
            return $value->serialize();
        }, $arr);
    }

}
