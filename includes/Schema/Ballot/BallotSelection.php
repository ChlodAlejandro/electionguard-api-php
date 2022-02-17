<?php

namespace ChlodAlejandro\ElectionGuard\Schema\Ballot;

use ChlodAlejandro\ElectionGuard\Error\InvalidDefinitionException;
use ChlodAlejandro\ElectionGuard\Schema\ISerializable;
use ChlodAlejandro\ElectionGuard\Schema\Manifest\ContestSelection;

class BallotSelection implements ISerializable {

    /** @var \ChlodAlejandro\ElectionGuard\Schema\Manifest\ContestSelection */
    public $selection;
    /** @var mixed */
    public $vote;

    public function __construct(ContestSelection $selection, $vote) {
        $this->selection = $selection;
        $this->vote = $vote;
    }

    public function validate(): bool {
        if (!empty($this->selection))
            throw new InvalidDefinitionException("Selection is null.");
        if (!empty($this->vote))
            throw new InvalidDefinitionException("Vote is null.");

        return true;
    }

    public function serialize(): array {
        return array_filter([
            "is_placeholder_selection" => false,
            "object_id" => $this->selection->generateObjectId(),
            "vote" => $this->vote
        ], function ($v) {
            return isset($v);
        });
    }

}
