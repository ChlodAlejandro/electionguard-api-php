<?php

namespace ChlodAlejandro\ElectionGuard\Schema\Manifest;

use ChlodAlejandro\ElectionGuard\Error\InvalidDefinitionException;
use ChlodAlejandro\ElectionGuard\Schema\ISerializable;
use ChlodAlejandro\ElectionGuard\Utilities;

class Candidate implements ISerializable {

    /**
     * The name of the candidate.
     * @var TextContainer
     */
    public $name;
    /**
     * The party of the candidate.
     * @var Party
     */
    public $party;
    /**
     * A URI to the candidate's image.
     * @var string
     */
    public $imageUri;

    public function getName(): TextContainer {
        return $this->name;
    }

    public function setName(TextContainer $name): Candidate {
        $this->name = $name;

        return $this;
    }

    public function getParty(): Party {
        return $this->party;
    }

    public function setParty(Party $party): Candidate {
        $this->party = $party;

        return $this;
    }

    public function getImageUri(): string {
        return $this->imageUri;
    }

    public function setImageUri(string $imageUri): Candidate {
        $this->imageUri = $imageUri;

        return $this;
    }

    /**
     * @see TextContainer::generateObjectId()
     */
    public function generateObjectId(): string {
        return $this->name->generateObjectId();
    }

    public function serialize(): array {
        return array_filter([
            "object_id" => $this->generateObjectId(),
            "name" => $this->name->serialize(),
            "party_id" => isset($this->party) ? $this->party->generateObjectId() : null,
            "image_uri" => $this->imageUri
        ], function ($v) {
            return Utilities::filter($v);
        });
    }

    public function validate(): bool {
        if (!isset($this->name))
            throw new InvalidDefinitionException("Candidate name is null.");

        $this->name->validate();

        return true;
    }

}
