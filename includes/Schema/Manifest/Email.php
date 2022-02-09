<?php
namespace ChlodAlejandro\ElectionGuard\Schema\Manifest;

use ChlodAlejandro\ElectionGuard\Constants;
use ChlodAlejandro\ElectionGuard\Error\InvalidDefinitionException;

class Email extends AnnotatedContactPoint {

    /**
     * @inheritDoc
     */
    public function validate(): bool {
        if (!isset($this->annotation))
            throw new InvalidDefinitionException("Email annotation is null");
        if (!isset($this->value))
            throw new InvalidDefinitionException("Email address is null");

        if (!preg_match(Constants::EMAIL_REGEX, $this->value)) {
            throw new InvalidDefinitionException("Email is not valid.");
        }

        return true;
    }

}
