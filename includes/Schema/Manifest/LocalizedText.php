<?php

namespace ChlodAlejandro\ElectionGuard\Schema\Manifest;

use ChlodAlejandro\ElectionGuard\Error\InvalidDefinitionException;
use ChlodAlejandro\ElectionGuard\Schema\ISerializable;

class LocalizedText implements ISerializable {

    /**
     * The localized text.
     * @var string
     */
    public $value;
    /**
     * The ISO-639-1 code of the language.
     * @var string
     */
    public $language;

    /**
     * @param $language string The ISO-639-1 code of the language.
     * @param $value string The localized text.
     */
    public function __construct(string $language, string $value) {
        $this->value = $value;
        $this->language = $language;
    }

    /**
     * @inheritDoc
     */
    public function serialize(): array {
        return [
            "value" => $this->value,
            "language" => $this->language
        ];
    }

    /**
     * @inheritDoc
     */
    public function validate(): bool {
        if (!isset($this->value))
            throw new InvalidDefinitionException("Localized text is null.");
        if (!isset($this->language))
            throw new InvalidDefinitionException("Text language is null.");

        return true;
    }

}
