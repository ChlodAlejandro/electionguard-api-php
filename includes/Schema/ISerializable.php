<?php

namespace ChlodAlejandro\ElectionGuard\Schema;

use ChlodAlejandro\ElectionGuard\Error\InvalidDefinitionException;

interface ISerializable {

    /**
     * Serialize the object to a schema JSON-compatible format.
     * @return array
     */
    public function serialize(): array;

    /**
     * Determine if this object can be serialized without problems.
     * @return bool
     * @throws InvalidDefinitionException
     */
    public function validate(): bool;

}
