<?php

namespace ChlodAlejandro\ElectionGuard\Schema\Guardian;

use ChlodAlejandro\ElectionGuard\API\GuardianGenerationInfo;
use ChlodAlejandro\ElectionGuard\Schema\ISerializable;
use ChlodAlejandro\ElectionGuard\Utilities;
use stdClass;

class Guardian extends GuardianGenerationInfo implements ISerializable {

    /** @var \ChlodAlejandro\ElectionGuard\Schema\Guardian\ElectionKeyPair */
    private $electionKeyPair;
    /** @var \ChlodAlejandro\ElectionGuard\Schema\Guardian\AuxiliaryKeyPair */
    private $auxiliaryKeyPair;

    /**
     * @param string|\stdClass|array $json
     * @return \ChlodAlejandro\ElectionGuard\Schema\Guardian\Guardian
     */
    public static function guardianFromJson($json): Guardian {
        $data = is_string($json)
            ? json_decode($json, false)
            : ($json instanceof stdClass ? $json : json_decode(json_encode($json), false));

        return new Guardian(
            parent::ggiFromJson($json),
            ElectionKeyPair::fromJson($data->election_key_pair),
            AuxiliaryKeyPair::fromJson($data->auxiliary_key_pair),
        );
    }

    public function __construct(
        GuardianGenerationInfo $ggi,
        ElectionKeyPair $electionKeyPair,
        AuxiliaryKeyPair $auxiliaryKeyPair
    ) {
        parent::__construct(
            $ggi->generateObjectId(),
            $ggi->getGuardianCount(),
            $ggi->getQuorum(),
            $ggi->getSequenceOrder()
        );
        $this->electionKeyPair = $electionKeyPair;
        $this->auxiliaryKeyPair = $auxiliaryKeyPair;
    }

    /**
     * @return \ChlodAlejandro\ElectionGuard\Schema\Guardian\ElectionKeyPair
     */
    public function getElectionKeyPair(): ElectionKeyPair {
        return $this->electionKeyPair;
    }

    /**
     * @return \ChlodAlejandro\ElectionGuard\Schema\Guardian\AuxiliaryKeyPair
     */
    public function getAuxiliaryKeyPair(): AuxiliaryKeyPair {
        return $this->auxiliaryKeyPair;
    }

    public function serialize(): array {
        return array_filter(array_merge(parent::serialize(), [
            "election_key_pair" => $this->electionKeyPair->serialize(),
            "auxiliary_key_pair" => $this->auxiliaryKeyPair->serialize()
        ]), function ($v) { return Utilities::filter($v); });
    }

    public function validate(): bool {
        parent::validate();
        $this->electionKeyPair->validate();
        $this->auxiliaryKeyPair->validate();

        return true;
    }

}
