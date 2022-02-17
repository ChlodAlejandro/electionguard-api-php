<?php

namespace ChlodAlejandro\ElectionGuard\Schema\Manifest;

use ChlodAlejandro\ElectionGuard\Error\InvalidDefinitionException;
use ChlodAlejandro\ElectionGuard\Schema\ISerializable;
use ChlodAlejandro\ElectionGuard\Utilities;

/**
 * Represents a geopolitical unit.
 */
class GeopoliticalUnit implements ISerializable {

    /**
     * Defines all allowed geopolitical unit types.
     * @var string[]
     */
    const allowedTypes = [
        "unknown", "ballot_batch", "ballot_style_area", "borough", "city", "city_council",
        "combined_precinct", "congressional", "country", "county", "county_council",
        "drop_box", "judicial", "municipality", "polling_place", "precinct", "school",
        "special", "split_precinct", "state", "state_house", "state_senate", "township",
        "utility", "village", "vote_center", "ward", "water", "other"
    ];

    /**
     * The name of this geopolitical unit.
     * @var string
     */
    public $name;

    /**
     * Represents the type of this geopolitical unit.
     *
     * Valid values: `unknown`, `ballot_batch`, `ballot_style_area`, `borough`, `city`,
     * `city_council`, `combined_precinct`, `congressional`, `country`, `county`,
     * `county_council`, `drop_box`, `judicial`, `municipality`, `polling_place`,
     * `precinct`, `school`, `special`, `split_precinct`, `state`, `state_house`,
     * `state_senate`, `township`, `utility`, `village`, `vote_center`, `ward`,
     * `water`, `other`.
     *
     * @var string
     */
    public $type;

    /**
     * Represents geopolitical unit contact information.
     * @var ContactInformation
     */
    public $contactInformation;

    public function getName(): string {
        return $this->name;
    }

    public function setName(string $name): GeopoliticalUnit {
        $this->name = $name;

        return $this;
    }

    public function getType(): string {
        return $this->type;
    }

    /**
     * @param string $type
     * @return GeopoliticalUnit
     * @throws \ChlodAlejandro\ElectionGuard\Error\InvalidDefinitionException
     */
    public function setType(string $type): GeopoliticalUnit {
        if (!in_array($type, GeopoliticalUnit::allowedTypes)) {
            throw new InvalidDefinitionException("Invalid type: " . $type);
        }
        $this->type = $type;

        return $this;
    }

    public function getContactInformation(): ContactInformation {
        return $this->contactInformation;
    }

    public function setContactInformation(ContactInformation $contactInformation): GeopoliticalUnit {
        $this->contactInformation = $contactInformation;

        return $this;
    }

    /**
     * Generate an object ID from this Name.
     * @return string
     */
    public function generateObjectId(): string {
        return Utilities::camelToSnakeCase($this->name);
    }

    /**
     * @inheritDoc
     */
    public function serialize(): array {
        return array_filter([
            "object_id" => $this->generateObjectId(),
            "name" => $this->name,
            "type" => $this->type,
            "contact_information" => $this->contactInformation->serialize()
        ], function ($v) {
            return Utilities::filter($v);
        });
    }

    /**
     * @inheritDoc
     */
    function validate(): bool {
        if (!isset($this->name))
            throw new InvalidDefinitionException("Manifest name is null.");
        if (!isset($this->type))
            throw new InvalidDefinitionException("Type is null.");
        if (!isset($this->contactInformation))
            throw new InvalidDefinitionException("Geopolitical contact information is null.");

        if (strlen($this->name) > 256) {
            throw new InvalidDefinitionException("Geopolitical unit name is longer than 256 bytes.");
        }
        if (!in_array($this->type, self::allowedTypes)) {
            throw new InvalidDefinitionException("Invalid geopolitical unit type.");
        }
        $this->contactInformation->validate();

        return true;
    }

}
