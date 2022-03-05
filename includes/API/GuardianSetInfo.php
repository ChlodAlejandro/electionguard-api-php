<?php

namespace ChlodAlejandro\ElectionGuard\API;

class GuardianSetInfo {

    /** @var int Number of guardians to be generated. */
    private $guardianCount;
    /** @var int Number of guardians required to successfully perform a tally decryption. */
    private $quorum;

    public function __construct(int $guardianCount, ?int $quorum = null) {
        $this->guardianCount = $guardianCount;
        $this->quorum = $quorum ?? $guardianCount;
    }

    public function getGuardianCount(): int {
        return $this->guardianCount;
    }

    public function getQuorum(): int {
        return $this->quorum;
    }


}
