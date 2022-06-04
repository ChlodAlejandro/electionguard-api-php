<?php

namespace ChlodAlejandro\ElectionGuard\Schema\Manifest;

use ChlodAlejandro\ElectionGuard\Schema\IDeserializable;
use ChlodAlejandro\ElectionGuard\Utilities;

/**
 * Represents contact information of a user.
 */
class ContactInformation implements IDeserializable {

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

    public static function deserialize(array $data): ContactInformation {
        $contactInformation = new ContactInformation();

        $contactInformation->name = $data["name"];
        if (isset($data["address_line"]))
            $contactInformation->addressLine = $data["address_line"];
        if (isset($data["email"]))
            $contactInformation->email = SerializableUtils::deserializeArray(function ($data) {
                return new Email($data["annotation"], $data["value"]);
            }, $data["email"]);
        if (isset($data["phone"]))
            $contactInformation->phone = SerializableUtils::deserializeArray(function ($data) {
                return new Phone($data["annotation"], $data["value"]);
            }, $data["phone"]);

        return $contactInformation;
    }

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
     * @param \ChlodAlejandro\ElectionGuard\Schema\Manifest\Email[]|null $email
     * @return ContactInformation
     */
    public function setEmail(?array $email): ContactInformation {
        $this->email = $email;

        return $this;
    }

    /**
     * @param \ChlodAlejandro\ElectionGuard\Schema\Manifest\Email[]|null $phone
     * @return ContactInformation
     */
    public function setPhone(?array $phone): ContactInformation {
        $this->phone = $phone;

        return $this;
    }

}
