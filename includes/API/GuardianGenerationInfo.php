<?php

namespace ChlodAlejandro\ElectionGuard\API;

use ChlodAlejandro\ElectionGuard\Error\InvalidDefinitionException;
use ChlodAlejandro\ElectionGuard\Schema\ISerializable;
use ChlodAlejandro\ElectionGuard\Utilities;

class GuardianGenerationInfo implements ISerializable {

    /** @var string Guardian ID template. */
    private $id;
    /** @var int Number of guardians to be generated. */
    private $guardianCount;
    /** @var int Number of guardians required to successfully perform a tally decryption. */
    private $quorum;
    /** @var int Current guardian generation sequence number. */
    private $sequenceOrder;

    public function __construct(string $id, int $guardianCount, ?int $quorum = null, int $sequenceOrder = 0) {
        $this->id = $id;
        $this->guardianCount = $guardianCount;
        $this->quorum = $quorum ?? $guardianCount;

        $this->sequenceOrder = $sequenceOrder ?? 0;
    }

    public function generateObjectId(): string {
        return $this->getId() . "_" . $this->getSequenceOrder();
    }

    public function getId(): string {
        return $this->id;
    }

    public function getGuardianCount(): int {
        return $this->guardianCount;
    }

    public function getQuorum(): int {
        return $this->quorum;
    }

    public function getSequenceOrder(): int {
        return $this->sequenceOrder;
    }

    public function bumpSequenceOrder(): int {
        return ++$this->sequenceOrder;
    }

    public function serialize(): array {
        return array_filter([
            "id" => $this->generateObjectId(),
            "number_of_guardians" => $this->getGuardianCount(),
            "quorum" => $this->getQuorum(),
            "sequence_order" => $this->getSequenceOrder()
        ], function ($v) { return Utilities::filter($v); });
    }

    public function validate(): bool {
        return true;
    }

}
