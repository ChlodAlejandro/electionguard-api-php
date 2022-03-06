<?php

namespace ChlodAlejandro\ElectionGuard\Schema\Manifest;

use ChlodAlejandro\ElectionGuard\Error\InvalidDefinitionException;
use ChlodAlejandro\ElectionGuard\Schema\ISerializable;
use ChlodAlejandro\ElectionGuard\Utilities;

class Contest implements ISerializable {

    /**
     * A list of allowed {@link Contest::$voteVariation} values.
     */
    const allowedVariations = [
        "unknown", "one_of_m", "approval", "borda", "cumulative",
        "majority", "n_of_m", "plurality", "proportional", "range", "rcv",
        "super_majority", "other"
    ];

    /**
     * The electoral district that this contest is part of.
     * @var GeopoliticalUnit
     */
    public $electoralDistrict;
    /**
     * The order that this contest appears in the ballot.
     * @var int
     */
    public $sequenceOrder;
    /**
     * Tallying method for this contest.
     *
     * Valid values: `unknown`, `one_of_m`, `approval`, `borda`, `cumulative`,
     * `majority`, `n_of_m`, `plurality`, `proportional`, `range`, `rcv`,
     * `super_majority`, `other`
     *
     * @var string
     */
    public $voteVariation;
    /**
     * The number of selections elected for this contest.
     * @var int
     */
    public $numberElected;
    /**
     * The number of votes allowed for each voter.
     * @var null|int
     */
    public $votesAllowed;
    /**
     * The name of this contest.
     * @var string
     */
    public $name;
    /**
     * A list of possible ballot selections for this contest.
     * @var ContestSelection[]
     */
    public $ballotSelections;
    /**
     * Title for the contest.
     * @var TextContainer
     */
    public $ballotTitle;
    /**
     * Subtitle for the contest.
     * @var ?TextContainer
     */
    public $ballotSubtitle;

    /**
     * Generate an object ID from this Contest.
     * @return string
     */
    public function generateObjectId(): string {
        return Utilities::camelToSnakeCase($this->name);
    }

    /**
     * @return \ChlodAlejandro\ElectionGuard\Schema\Manifest\ContestSelection[]
     */
    public function getBallotSelections(): array {
        return $this->ballotSelections;
    }

    public function addBallotSelectionFromCandidate(Candidate ...$candidates): Contest {
        if ($this->ballotSelections == null)
            $this->ballotSelections = [];
        foreach (($candidates ?? []) as $candidate)
            $this->ballotSelections[] = new ContestSelection(
                $this,
                count($this->ballotSelections),
                $candidate
            );

        return $this;
    }

    public function getElectoralDistrict(): GeopoliticalUnit {
        return $this->electoralDistrict;
    }

    public function setElectoralDistrict(GeopoliticalUnit $electoralDistrict): Contest {
        $this->electoralDistrict = $electoralDistrict;

        return $this;
    }

    public function getSequenceOrder(): int {
        return $this->sequenceOrder;
    }

    public function setSequenceOrder(int $sequenceOrder): Contest {
        $this->sequenceOrder = $sequenceOrder;

        return $this;
    }

    public function getVoteVariation(): string {
        return $this->voteVariation;
    }

    /**
     * @param string $voteVariation
     * @return Contest
     * @throws \ChlodAlejandro\ElectionGuard\Error\InvalidDefinitionException
     */
    public function setVoteVariation(string $voteVariation): Contest {
        if (!in_array($voteVariation, Contest::allowedVariations)) {
            throw new InvalidDefinitionException("Invalid vote variation: " . $voteVariation);
        }
        $this->voteVariation = $voteVariation;

        return $this;
    }

    public function getNumberElected(): int {
        return $this->numberElected;
    }

    public function setNumberElected(int $numberElected): Contest {
        $this->numberElected = $numberElected;

        return $this;
    }

    public function getVotesAllowed(): ?int {
        return $this->votesAllowed;
    }

    public function setVotesAllowed(?int $votesAllowed): Contest {
        $this->votesAllowed = $votesAllowed;

        return $this;
    }

    public function getName(): string {
        return $this->name;
    }

    public function setName(string $name): Contest {
        $this->name = $name;

        return $this;
    }

    public function getBallotTitle(): TextContainer {
        return $this->ballotTitle;
    }

    public function setBallotTitle(TextContainer $ballotTitle): Contest {
        $this->ballotTitle = $ballotTitle;

        return $this;
    }

    public function getBallotSubtitle(): ?TextContainer {
        return $this->ballotSubtitle;
    }

    public function setBallotSubtitle(?TextContainer $ballotSubtitle): Contest {
        $this->ballotSubtitle = $ballotSubtitle;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function serialize(): array {
        return array_filter([
            "object_id" => $this->generateObjectId(),
            "electoral_district_id" => $this->electoralDistrict->generateObjectId(),
            "sequence_order" => $this->sequenceOrder,
            "vote_variation" => $this->voteVariation,
            "number_elected" => $this->numberElected,
            "votes_allowed" => $this->votesAllowed,
            "name" => $this->name,
            "ballot_selections" => SerializableUtils::serializeArray($this->ballotSelections),
            "ballot_title" => $this->ballotTitle,
            "ballot_subtitle" => $this->ballotSubtitle
        ], function ($v) {
            return Utilities::filter($v);
        });
    }

    /**
     * @inheritDoc
     */
    public function validate(): bool {
        if (!isset($this->electoralDistrict))
            throw new InvalidDefinitionException("Electoral district is null.");
        if (!isset($this->sequenceOrder))
            throw new InvalidDefinitionException("Contest sequence order is null.");
        if (!isset($this->voteVariation))
            throw new InvalidDefinitionException("Vote variation method is null.");
        if (!isset($this->numberElected))
            throw new InvalidDefinitionException("Number elected is null.");
        if (!isset($this->name))
            throw new InvalidDefinitionException("Contest name is null.");
        if (!isset($this->ballotSelections))
            throw new InvalidDefinitionException("Contest selections is null.");
        if (!isset($this->ballotTitle))
            throw new InvalidDefinitionException("Contest title is null.");

        if (strlen($this->generateObjectId()) > 256)
            throw new InvalidDefinitionException("Contest ID is longer than 256 bytes.");
        if (strlen($this->electoralDistrict->generateObjectId()) > 256)
            throw new InvalidDefinitionException("Electoral district ID is longer than 256 bytes.");
        if (strlen($this->name) > 256)
            throw new InvalidDefinitionException("Contest name is longer than 256 bytes.");

        if (count($this->ballotTitle->text) < 1)
            throw new InvalidDefinitionException("Contest must have a title.");
        if (!in_array($this->voteVariation, self::allowedVariations))
            throw new InvalidDefinitionException("Vote variation method is invalid.");

        foreach (($this->ballotSelections ?? []) as $selection) {
            $selection->validate();
        }
        $this->ballotTitle->validate();
        if (isset($this->ballotSubtitle))
            $this->ballotSubtitle->validate();

        return true;
    }

}
