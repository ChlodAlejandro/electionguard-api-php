<?php

namespace ChlodAlejandro\ElectionGuard\Schema;

interface IDeserializable extends ISerializable {

    /**
     * Deserialize from JSON data.
     * @param array $data
     * @return mixed
     */
    public static function deserialize(array $data);

}
