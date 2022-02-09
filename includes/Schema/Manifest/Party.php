<?php

namespace ChlodAlejandro\ElectionGuard\Schema\Manifest;

use ChlodAlejandro\ElectionGuard\Error\InvalidDefinitionException;
use ChlodAlejandro\ElectionGuard\Schema\ISerializable;

class Party implements ISerializable {

    /**
     * The party name.
     * @var TextContainer
     */
    public $name;
    /**
     * An abbreviation of the party name.
     * @var null|string
     */
    public $abbreviation;
    /**
     * The hexadecimal RGB color code of the party.
     * @var null|string
     */
    public $color;
    /**
     * A URI to the party's logo.
     * @var null|string
     */
    public $logoUri;

    /**
     * @see TextContainer::generateObjectId()
     */
    public function generateObjectId(): string {
        return $this->name->generateObjectId();
    }

    public function getName(): TextContainer {
        return $this->name;
    }

    public function setName(TextContainer $name): Party {
        $this->name = $name;

        return $this;
    }

    public function getAbbreviation(): ?string {
        return $this->abbreviation;
    }

    public function setAbbreviation(?string $abbreviation): Party {
        $this->abbreviation = $abbreviation;

        return $this;
    }

    public function getColor(): ?string {
        return $this->color;
    }

    public function setColor(?string $color): Party {
        $this->color = $color;

        return $this;
    }

    public function getLogoUri(): ?string {
        return $this->logoUri;
    }

    public function setLogoUri(?string $logoUri): Party {
        $this->logoUri = $logoUri;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function serialize(): array {
        return array_filter([
            "object_id" => $this->generateObjectId(),
            "name" => $this->name->serialize(),
            "abbreviation" => $this->abbreviation,
            "color" => $this->color,
            "logo_uri" => $this->logoUri
        ], function ($v) {
            return Manifest::filter($v);
        });
    }

    /**
     * @inheritDoc
     */
    public function validate(): bool {
        if (!isset($this->name))
            throw new InvalidDefinitionException("Party name is null.");

        $this->name->validate();

        return true;
    }

}
