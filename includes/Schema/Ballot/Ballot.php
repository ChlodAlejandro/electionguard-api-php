<?php

namespace ChlodAlejandro\ElectionGuard\Schema\Ballot;

use ChlodAlejandro\ElectionGuard\Error\InvalidDefinitionException;
use ChlodAlejandro\ElectionGuard\Schema\ISerializable;
use ChlodAlejandro\ElectionGuard\Schema\Manifest\BallotStyle;
use ChlodAlejandro\ElectionGuard\Schema\Manifest\Manifest;
use ChlodAlejandro\ElectionGuard\Schema\Manifest\SerializableUtils;

class Ballot implements ISerializable {

    /** @var string */
    private $objectId;
    /** @var Manifest */
    private $manifest;
    /** @var BallotStyle */
    private $ballotStyle;
    /** @var \ChlodAlejandro\ElectionGuard\Schema\Ballot\BallotContest[] */
    private $contests;

    /**
     * @param string $objectId
     * @param \ChlodAlejandro\ElectionGuard\Schema\Manifest\Manifest $manifest
     * @param \ChlodAlejandro\ElectionGuard\Schema\Manifest\BallotStyle|null $ballotStyle
     * @param \ChlodAlejandro\ElectionGuard\Schema\Ballot\BallotContest[] $contests
     */
    public function __construct(
        string $objectId,
        Manifest $manifest,
        BallotStyle $ballotStyle = null,
        array $contests = null
    ) {
        $this->objectId = $objectId;
        $this->manifest = $manifest;
        $this->ballotStyle = $ballotStyle;
        $this->contests = $contests;
    }

    /**
     * @param string $objectId
     * @return Ballot
     */
    public function setObjectId(string $objectId): Ballot {
        $this->objectId = $objectId;

        return $this;
    }

    /**
     * @param \ChlodAlejandro\ElectionGuard\Schema\Manifest\Manifest $manifest
     * @return Ballot
     */
    public function setManifest(Manifest $manifest): Ballot {
        $this->manifest = $manifest;

        return $this;
    }

    /**
     * @param \ChlodAlejandro\ElectionGuard\Schema\Manifest\BallotStyle $ballotStyle
     * @return Ballot
     */
    public function setBallotStyle(BallotStyle $ballotStyle): Ballot {
        $this->ballotStyle = $ballotStyle;

        return $this;
    }

    /**
     * @param \ChlodAlejandro\ElectionGuard\Schema\Ballot\BallotContest ...$contests
     * @return Ballot
     */
    public function addContest(BallotContest ...$contests): Ballot {
        foreach ($contests as $contest) {
            $this->contests[] = $contest;
        }
        return $this;
    }

    public function serialize(): array {
        return array_filter([
            "object_id" => $this->objectId,
            "ballot_style" => $this->ballotStyle->generateObjectId(),
            "contests" => SerializableUtils::serializeArray($this->contests)
        ]);
    }

    public function validate(): bool {
        if (!isset($this->objectId))
            throw new InvalidDefinitionException("ID is null.");
        if (!isset($this->ballotStyle))
            throw new InvalidDefinitionException("Ballot style is null.");
        if (!isset($this->contests))
            throw new InvalidDefinitionException("Ballot options is null.");

        if (isset($this->manifest)) {
            if (!in_array($this->ballotStyle, $this->manifest->ballotStyles))
                throw new InvalidDefinitionException("Manifest does not contain the target ballot style.");

            $this->manifest->getBallotStyleContests($this->ballotStyle);
        }

        $ballotStyleContests = $this->manifest->getBallotStyleContests($this->ballotStyle);
        foreach ($this->contests as $contest) {
            $contest->validate();
            if (!in_array($contest->getContest(), $ballotStyleContests))
                throw new InvalidDefinitionException("Manifest does not contain the target ballot style.");
        }

        return true;
    }

    /**
     * @return string
     */
    public function getObjectId(): string {
        return $this->objectId;
    }

    /**
     * @return \ChlodAlejandro\ElectionGuard\Schema\Manifest\Manifest
     */
    public function getManifest(): Manifest {
        return $this->manifest;
    }

    /**
     * @return \ChlodAlejandro\ElectionGuard\Schema\Manifest\BallotStyle
     */
    public function getBallotStyle(): ?BallotStyle {
        return $this->ballotStyle;
    }

    /**
     * @return \ChlodAlejandro\ElectionGuard\Schema\Ballot\BallotContest[]
     */
    public function getContests(): ?array {
        return $this->contests;
    }

}
