<?php

namespace ChlodAlejandro\ElectionGuard\Schema\Manifest;

use ChlodAlejandro\ElectionGuard\Schema\ISerializable;
use ChlodAlejandro\ElectionGuard\Utilities;

/**
 * Represents contact information of a user.
 */
class ContactInformation implements ISerializable {

    /**
     * Name of the contact person.
     * @var null|string
     */
    public $name;

    /**
     * Address lines of the user.
     * @var null|string[]
     */
    public $addressLine;

    /**
     * Email address contacts of the geopolitical unit.
     * @var null|Email[]
     */
    public $email;

    /**
     * Phone number contacts of the geopolitical unit.
     * @var null|Email[]
     */
    public $phone;

    /**
     * @inheritDoc
     */
    public function serialize(): array {
        return array_filter([
            "address_line" => $this->addressLine,
            "name" => $this->name,
            "email" => SerializableUtils::serializeArray($this->email),
            "phone" => SerializableUtils::serializeArray($this->phone)
        ], function ($v) {
            return Utilities::filter($v);
        });
    }

    public function getName(): ?string {
        return $this->name;
    }

    public function setName(?string $name): ContactInformation {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string[]|null
     */
    public function getAddressLine(): ?array {
        return $this->addressLine;
    }

    /**
     * @param string[]|null $addressLine
     * @return ContactInformation
     */
    public function setAddressLines(?array $addressLine): ContactInformation {
        $this->addressLine = $addressLine;

        return $this;
    }

    public function addEmail(Email $email): ContactInformation {
        if ($this->email == null)
            $this->email = [];
        $this->email[] = $email;

        return $this;
    }

    public function addPhone(Phone $phone): ContactInformation {
        if ($this->phone == null)
            $this->phone = [];
        $this->phone[] = $phone;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function validate(): bool {

        foreach (($this->email ?? []) as $email) {
            $email->validate();
        }
        foreach (($this->phone ?? []) as $phone) {
            $phone->validate();
        }

        return true;
    }

}
