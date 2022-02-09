<?php

namespace ChlodAlejandro\ElectionGuard\Schema\Manifest;

use ChlodAlejandro\ElectionGuard\Error\InvalidDefinitionException;
use ChlodAlejandro\ElectionGuard\Schema\ISerializable;

class ContestSelection implements ISerializable {

    /**
     * The contest that this selection is part of. This is not part
     * of the Manifest schema, but is included for object ID generation.
     * @var Contest
     */
    public $contest;
    /**
     * The candidate that this selection refers to.
     * @var Candidate
     */
    public $candidate;
    /**
     * The order of the candidate in the ballot.
     * @var int
     */
    public $sequenceOrder;

    public function __construct(Contest $contest, int $sequenceOrder, Candidate $candidate) {
        $this->contest = $contest;
        $this->sequenceOrder = $sequenceOrder;
        $this->candidate = $candidate;
    }

    /**
     * Generate an object ID from this ContestSelection.
     * @return string
     */
    function generateObjectId(): string {
        return sprintf(
            "%s-%s-selection",
            substr($this->candidate->generateObjectId(), 0, 120),
            substr($this->contest->generateObjectId(), 0, 120)
        );
    }

    /**
     * @inheritDoc
     */
    public function serialize(): array {
        return [
            "object_id" => $this->generateObjectId(),
            "candidate_id" => $this->candidate->generateObjectId(),
            "sequence_order" => $this->sequenceOrder
        ];
    }

    /**
     * @inheritDoc
     */
    public function validate(): bool {
        if (!isset($this->contest))
            throw new InvalidDefinitionException("Contest selection context is null.");
        if (!isset($this->candidate))
            throw new InvalidDefinitionException("Contest selection candidate is null.");
        if (!isset($this->sequenceOrder))
            throw new InvalidDefinitionException("Contest selection sequence order is null.");

        return true;
    }

}
