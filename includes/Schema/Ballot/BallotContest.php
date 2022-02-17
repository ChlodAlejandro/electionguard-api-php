<?php

namespace ChlodAlejandro\ElectionGuard\Schema\Ballot;

use ChlodAlejandro\ElectionGuard\Error\InvalidDefinitionException;
use ChlodAlejandro\ElectionGuard\Schema\ISerializable;
use ChlodAlejandro\ElectionGuard\Schema\Manifest\SerializableUtils;

class BallotContest implements ISerializable {

    /** @var \ChlodAlejandro\ElectionGuard\Schema\Manifest\Contest */
    private $contest;
    /** @var \ChlodAlejandro\ElectionGuard\Schema\Ballot\BallotSelection[] */
    private $selections;

    /**
     * @return \ChlodAlejandro\ElectionGuard\Schema\Manifest\Contest
     */
    public function getContest(): \ChlodAlejandro\ElectionGuard\Schema\Manifest\Contest {
        return $this->contest;
    }

    /**
     * @return \ChlodAlejandro\ElectionGuard\Schema\Ballot\BallotSelection[]
     */
    public function getSelections(): array {
        return $this->selections;
    }

    /**
     * @param \ChlodAlejandro\ElectionGuard\Schema\Manifest\Contest $contest
     * @return \ChlodAlejandro\ElectionGuard\Schema\Ballot\BallotContest
     */
    public function setContest(\ChlodAlejandro\ElectionGuard\Schema\Manifest\Contest $contest): BallotContest {
        $this->contest = $contest;

        return $this;
    }

    /**
     * @param \ChlodAlejandro\ElectionGuard\Schema\Ballot\BallotSelection[] $selections
     * @return \ChlodAlejandro\ElectionGuard\Schema\Ballot\BallotContest
     */
    public function setSelections(array $selections): BallotContest {
        $this->selections = $selections;

        return $this;
    }

    /**
     * @param \ChlodAlejandro\ElectionGuard\Schema\Ballot\BallotSelection ...$selections
     * @return \ChlodAlejandro\ElectionGuard\Schema\Ballot\BallotContest
     */
    public function addSelection(BallotSelection ...$selections): BallotContest {
        foreach ($selections as $selection) {
            $this->selections[] = $selection;
        }

        return $this;
    }

    public function __construct($contest = null, $selections = null) {
        $this->contest = $contest;
        $this->selections = $selections;
    }

    /**
     * @inheritDoc
     */
    public function serialize(): array {
        return [
            "object_id" => $this->contest->generateObjectId(),
            "ballot_selections" => SerializableUtils::serializeArray($this->selections)
        ];
    }

    /**
     * @inheritDoc
     */
    public function validate(): bool {
        if (!isset($this->contest))
            throw new InvalidDefinitionException("Contest of ballot contest is null.");
        if (!isset($this->selections))
            throw new InvalidDefinitionException("Ballot selections is null.");

        foreach ($this->selections as $selection) {
            $selection->validate();
        }

        return true;
    }

}
