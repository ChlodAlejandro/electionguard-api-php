<?php

namespace ChlodAlejandro\ElectionGuard\Schema\Manifest;

use ChlodAlejandro\ElectionGuard\Error\InvalidDefinitionException;
use ChlodAlejandro\ElectionGuard\Schema\ISerializable;
use ChlodAlejandro\ElectionGuard\Utilities;

class BallotStyle implements ISerializable {

    /**
     * The name of this ballot style. This is not defined in the Manifest
     * schema, but is used for generating the object ID.
     * @var string
     */
    public $name;

    /**
     * The geopolitical units involved in this ballot style.
     * @var GeopoliticalUnit[]|null
     */
    public $geopoliticalUnits;

    /**
     * The parties involved in this ballot style.
     * @var Party[]|null
     */
    public $parties;

    /**
     * A URI to an image that represents this ballot style.
     * @var string|null
     */
    public $imageUri;

    public function getName(): string {
        return $this->name;
    }

    public function setName(string $name): BallotStyle {
        $this->name = $name;

        return $this;
    }

    /**
     * @return \ChlodAlejandro\ElectionGuard\Schema\Manifest\GeopoliticalUnit[]|null
     */
    public function getGeopoliticalUnits(): ?array {
        return $this->geopoliticalUnits;
    }

    /**
     * @param \ChlodAlejandro\ElectionGuard\Schema\Manifest\GeopoliticalUnit[]|null $geopoliticalUnits
     * @return BallotStyle
     */
    public function setGeopoliticalUnits(?array $geopoliticalUnits): BallotStyle {
        $this->geopoliticalUnits = $geopoliticalUnits;

        return $this;
    }

    public function addGeopoliticalUnit(GeopoliticalUnit ...$geopoliticalUnits): BallotStyle {
        if ($this->geopoliticalUnits === null) {
            $this->geopoliticalUnits = [];
        }
        foreach (($geopoliticalUnits ?? []) as $geopoliticalUnit)
            $this->geopoliticalUnits[] = $geopoliticalUnit;

        return $this;
    }

    /**
     * @return \ChlodAlejandro\ElectionGuard\Schema\Manifest\Party[]|null
     */
    public function getParties(): ?array {
        return $this->parties;
    }

    /**
     * @param \ChlodAlejandro\ElectionGuard\Schema\Manifest\Party[]|null $parties
     * @return BallotStyle
     */
    public function setParties(?array $parties): BallotStyle {
        $this->parties = $parties;

        return $this;
    }

    public function getImageUri(): ?string {
        return $this->imageUri;
    }

    public function setImageUri(?string $imageUri): BallotStyle {
        $this->imageUri = $imageUri;

        return $this;
    }

    public function generateObjectId(): string {
        return Utilities::idSafe($this->name);
    }

    /**
     * @inheritDoc
     */
    public function serialize(): array {
        return array_filter([
            "object_id" => $this->generateObjectId(),
            "geopolitical_unit_ids" => isset($this->geopoliticalUnits) ? array_map(function ($geoUnit) {
                return $geoUnit->generateObjectId();
            }, $this->geopoliticalUnits) : null,
            "party_ids" => isset($this->parties) ? array_map(function ($party) {
                return $party->generateObjectId();
            }, $this->parties) : null,
            "image_uri" => $this->imageUri
        ], function ($v) {
            return Utilities::filter($v);
        });
    }

    /**
     * @inheritDoc
     */
    public function validate(): bool {
        if (!isset($this->name))
            throw new InvalidDefinitionException("Ballot style name is null.");

        return true;
    }

}
