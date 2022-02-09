<?php
namespace ChlodAlejandro\ElectionGuard\Schema\Manifest;

use ChlodAlejandro\ElectionGuard\Constants;
use ChlodAlejandro\ElectionGuard\Error\InvalidDefinitionException;

class Phone extends AnnotatedContactPoint {

    /**
     * @inheritDoc
     */
    public function validate(): bool {
        if (!isset($this->annotation))
            throw new InvalidDefinitionException("Phone annotation is null");
        if (!isset($this->value))
            throw new InvalidDefinitionException("Phone number is null");

        if (!preg_match(Constants::PHONE_REGEX, $this->value)) {
            throw new InvalidDefinitionException("Phone number is not valid.");
        }

        return true;
    }

}
