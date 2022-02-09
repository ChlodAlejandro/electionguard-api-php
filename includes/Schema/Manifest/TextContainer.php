<?php

namespace ChlodAlejandro\ElectionGuard\Schema\Manifest;

use ChlodAlejandro\ElectionGuard\Error\InvalidDefinitionException;
use ChlodAlejandro\ElectionGuard\Schema\ISerializable;
use ChlodAlejandro\ElectionGuard\Utilities;

class TextContainer implements ISerializable {

    /**
     * The text of this container.
     * @var LocalizedText[]
     */
    public $text;

    /**
     * @param LocalizedText[] $text
     */
    public function __construct(array $text) {
        $this->text = $text;
    }

    /**
     * Generate an object ID from this TextContainer.
     * @return string
     */
    public function generateObjectId(): string {
        $text = null;
        foreach (($this->text ?? []) as $localizedText) {
            if ($text == null) {
                $text = $localizedText->value;
                if ($localizedText->language === "en") break;
            } else if ($localizedText->language === "en") {
                $text = $localizedText->value;
                break;
            }
        }

        return Utilities::camelToSnakeCase($text);
    }

    public function addText(LocalizedText $text): TextContainer {
        if ($this->text == null)
            $this->text = [];
        $this->text[] = $text;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function serialize(): array {
        return [
            "text" => SerializableUtils::serializeArray($this->text)
        ];
    }

    /**
     * @inheritDoc
     */
    public function validate(): bool {
        if (!isset($this->text))
            throw new InvalidDefinitionException("Ballot name text is null.");

        foreach (($this->text ?? []) as $text)
            $text->validate();

        return true;
    }

}
