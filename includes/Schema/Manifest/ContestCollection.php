<?php

namespace ChlodAlejandro\ElectionGuard\Schema\Manifest;

class ContestCollection {

    /** @var array A list of contests. */
    private $contests = [];

    /**
     * @param array $contests A list of contests.
     */
    public function __construct(array $contests) {
        foreach (($contests ?? []) as $contest) {
            $this->addContest($contest);
        }
    }

    public function getContests(): array {
        return $this->contests;
    }

    public function setContests(array $contests): ContestCollection {
        $this->contests = $contests;

        return $this;
    }

    public function addContest(Contest $contest): ContestCollection {
        if ($this->contests == null)
            $this->contests = [];
        $contest->setSequenceOrder(count($this->contests));
        $this->contests[] = $contest;

        return $this;
    }

}
