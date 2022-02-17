<?php

namespace ChlodAlejandro\ElectionGuard\Schema\Manifest;

use ChlodAlejandro\ElectionGuard\Error\InvalidDefinitionException;
use ChlodAlejandro\ElectionGuard\Schema\ISerializable;
use ChlodAlejandro\ElectionGuard\Utilities;
use DateTime;
use InvalidArgumentException;

class Manifest implements ISerializable {

    /**
     * Spec version. As this schema is based on the v0.95 schema, this value is
     * set at a constant "v0.95".
     */
    const spec_version = "v0.95";
    /**
     * Allowed values for $type.
     */
    const allowedTypes = [
        "unknown", "general", "partisan_primary_closed", "partisan_primary_open",
        "primary", "runoff", "special", "other"
    ];

    /**
     * The name of this election.
     * @var TextContainer
     */
    public $name;

    /**
     * Contact information for the facilitators of this election.
     * @var ContactInformation
     */
    public $contactInformation;

    /**
     * The starting date of this election.
     * @var DateTime
     */
    public $startDate;

    /**
     * The ending date of this election.
     * @var null|DateTime
     */
    public $endDate;

    /**
     * The ID for this election's scope.
     * @var string
     */
    public $electionScopeId;

    /**
     * The type of this election.
     *
     * Valid values: `unknown`, `general`, `partisan_primary_closed`,
     * `partisan_primary_open`, `primary`, `runoff`, `special`, `other`.
     *
     * @var string
     */
    public $type;

    /**
     * Geopolitical units involved in this election.
     * @var GeopoliticalUnit[]
     */
    public $geopoliticalUnits;

    /**
     * Parties involved in this election.
     * @var Party[]
     */
    public $parties;

    /**
     * Candidates in this election.
     * @var Candidate[]
     */
    public $candidates;

    /**
     * Contests in this election.
     * @var Contest[]
     */
    public $contests;

    /**
     * The ballot styles for this election.
     * @var BallotStyle[]
     */
    public $ballotStyles;

    public function getName(): TextContainer {
        return $this->name;
    }

    public function setName(TextContainer $name): Manifest {
        $this->name = $name;

        return $this;
    }

    public function getContactInformation(): ContactInformation {
        return $this->contactInformation;
    }

    public function setContactInformation(ContactInformation $contactInformation): Manifest {
        $this->contactInformation = $contactInformation;

        return $this;
    }

    public function getStartDate(): DateTime {
        return $this->startDate;
    }

    public function setStartDate(DateTime $startDate): Manifest {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?DateTime {
        return $this->endDate;
    }

    public function setEndDate(?DateTime $endDate): Manifest {
        $this->endDate = $endDate;

        return $this;
    }

    public function getElectionScopeId(): string {
        return $this->electionScopeId;
    }

    public function setElectionScopeId(string $electionScopeId): Manifest {
        $this->electionScopeId = $electionScopeId;

        return $this;
    }

    public function getType(): string {
        return $this->type;
    }

    public function setType(string $type): Manifest {
        if (!in_array($type, self::allowedTypes)) {
            throw new InvalidArgumentException("Invalid type: $type");
        }
        $this->type = $type;

        return $this;
    }

    /**
     * @return \ChlodAlejandro\ElectionGuard\Schema\Manifest\GeopoliticalUnit[]
     */
    public function getGeopoliticalUnits(): array {
        return $this->geopoliticalUnits;
    }

    public function addGeopoliticalUnit(GeopoliticalUnit ...$geopoliticalUnits): Manifest {
        if ($this->geopoliticalUnits == null)
            $this->geopoliticalUnits = [];
        foreach (($geopoliticalUnits ?? []) as $geopoliticalUnit)
            $this->geopoliticalUnits[] = $geopoliticalUnit;

        return $this;
    }

    /**
     * @return \ChlodAlejandro\ElectionGuard\Schema\Manifest\Party[]
     */
    public function getParties(): array {
        return $this->parties;
    }

    public function addParty(Party ...$parties): Manifest {
        if ($this->parties == null)
            $this->parties = [];
        foreach (($parties ?? []) as $party)
            $this->parties[] = $party;

        return $this;
    }

    /**
     * @return \ChlodAlejandro\ElectionGuard\Schema\Manifest\Candidate[]
     */
    public function getCandidates(): array {
        return $this->candidates;
    }

    public function addCandidate(Candidate ...$candidates): Manifest {
        if ($this->candidates == null)
            $this->candidates = [];
        foreach (($candidates ?? []) as $candidate)
            $this->candidates[] = $candidate;

        return $this;
    }

    /**
     * @return \ChlodAlejandro\ElectionGuard\Schema\Manifest\Contest[]
     */
    public function getContests(): array {
        return $this->contests;
    }

    public function setContests(ContestCollection $collection): Manifest {
        $this->contests = $collection->getContests();

        return $this;
    }

    public function addContest(Contest ...$contests): Manifest {
        if ($this->contests == null)
            $this->contests = [];
        foreach (($contests ?? []) as $contest)
            $this->contests[] = $contest;

        return $this;
    }

    /**
     * @return \ChlodAlejandro\ElectionGuard\Schema\Manifest\BallotStyle[]
     */
    public function getBallotStyles(): array {
        return $this->ballotStyles;
    }

    public function addBallotStyle(BallotStyle ...$ballotStyles): Manifest {
        if ($this->ballotStyles == null)
            $this->ballotStyles = [];
        foreach (($ballotStyles ?? []) as $ballotStyle)
            $this->ballotStyles[] = $ballotStyle;

        return $this;
    }

    /**
     * Get all contests of a specific ballot style.
     * @param \ChlodAlejandro\ElectionGuard\Schema\Manifest\BallotStyle $ballotStyle
     * @return \ChlodAlejandro\ElectionGuard\Schema\Manifest\Contest[]
     */
    public function getBallotStyleContests(BallotStyle $ballotStyle): array {
        return array_filter($this->contests ?? [], function (Contest $contest) use ($ballotStyle) {
            return in_array($contest->electoralDistrict, $ballotStyle->geopoliticalUnits);
        });
    }

    /**
     * @inheritDoc
     */
    function serialize(): array {
        return array_filter([
            "spec_version" => Manifest::spec_version,
            "name" => $this->name->serialize(),
            "type" => $this->type,
            "election_scope_id" => $this->electionScopeId,
            "contact_information" => $this->contactInformation->serialize(),
            "start_date" => $this->startDate->format("c"),
            "end_date" => $this->endDate !== null ? $this->endDate->format("c") : null,
            "geopolitical_units" => SerializableUtils::serializeArray($this->geopoliticalUnits),
            "parties" => SerializableUtils::serializeArray($this->parties),
            "candidates" => SerializableUtils::serializeArray($this->candidates),
            "contests" => SerializableUtils::serializeArray($this->contests),
            "ballot_styles" => SerializableUtils::serializeArray($this->ballotStyles)
        ], function ($v) {
            return Utilities::filter($v);
        });
    }

    /**
     * @inheritDoc
     */
    public function validate(): bool {
        if (!isset($this->name))
            throw new InvalidDefinitionException("Manifest name is null.");
        if (!isset($this->contactInformation))
            throw new InvalidDefinitionException("Contact information is null.");
        if (!isset($this->startDate))
            throw new InvalidDefinitionException("Start date is null.");
        if (!isset($this->electionScopeId))
            throw new InvalidDefinitionException("Election scope ID is null.");
        if (!isset($this->type))
            throw new InvalidDefinitionException("Manifest type is null.");
        if (!isset($this->geopoliticalUnits))
            throw new InvalidDefinitionException("Manifest geopolitical units is null.");
        if (!isset($this->parties))
            throw new InvalidDefinitionException("Manifest parties is null.");
        if (!isset($this->candidates))
            throw new InvalidDefinitionException("Manifest candidates is null.");
        if (!isset($this->ballotStyles))
            throw new InvalidDefinitionException("Manifest ballot styles is null.");

        $this->name->validate();
        if (!in_array($this->type, self::allowedTypes))
            throw new InvalidDefinitionException("Manifest type is not valid.");

        if (count($this->contests) < 1)
            throw new InvalidDefinitionException("No contests found.");
        if (count($this->ballotStyles) < 1)
            throw new InvalidDefinitionException("No ballot styles found.");

        $geoUnitIDs = array_map(function ($geoUnit) {
            return $geoUnit->generateObjectId();
        }, $this->geopoliticalUnits);
        $partyIDs = array_map(function ($party) {
            return $party->generateObjectId();
        }, $this->parties);
        $candidateIDs = array_map(function ($candidate) {
            return $candidate->generateObjectId();
        }, $this->candidates);

        foreach (($this->geopoliticalUnits ?? []) as $geoUnit) {
            $geoUnit->validate();
        }
        foreach (($this->parties ?? []) as $party) {
            $party->validate();
        }
        foreach (($this->candidates ?? []) as $candidate) {
            $candidate->validate();
            if (!in_array($candidate->party->generateObjectId(), $partyIDs))
                throw new InvalidDefinitionException("Party ID referenced in candidate not found.");
        }
        foreach (($this->contests ?? []) as $contest) {
            $contest->validate();
            if (!in_array($contest->electoralDistrict->generateObjectId(), $geoUnitIDs))
                throw new InvalidDefinitionException("Electoral district referenced in contest not found.");
            foreach (($contest->ballotSelections ?? []) as $selection) {
                if (!in_array($selection->candidate->generateObjectId(), $candidateIDs))
                    throw new InvalidDefinitionException("Candidate referenced in contest not found.");
            }
        }
        foreach (($this->ballotStyles ?? []) as $ballotStyle) {
            $ballotStyle->validate();
            foreach (($ballotStyle->geopoliticalUnits ?? []) as $geoUnit) {
                if (!in_array($geoUnit->generateObjectId(), $geoUnitIDs))
                    throw new InvalidDefinitionException("Geopolitical unit referenced in ballot style not found.");
            }
            foreach (($ballotStyle->parties ?? []) as $party) {
                if (!in_array($party->generateObjectId(), $partyIDs))
                    throw new InvalidDefinitionException("Party referenced in ballot style not found.");
            }
        }

        return true;
    }

}
