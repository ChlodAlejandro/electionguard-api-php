<?php

namespace ChlodAlejandro\ElectionGuard\Schema\Manifest;

class WriteInCandidate extends Candidate {

    public $name;
    public $party = null;
    public $imageUri = null;

    public function __construct() {
        $this->name = new TextContainer([
            new LocalizedText("en", "Write-in candidate")
        ]);
    }

    public function serialize(): array {
        return [
            "object_id" => "write_in",
            "name" => $this->name->serialize(),
            "is_write_in" => true
        ];
    }

    public function validate(): bool {
        return true;
    }

}
