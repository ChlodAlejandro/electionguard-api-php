<?php

namespace ChlodAlejandro\ElectionGuard\API;

use ChlodAlejandro\ElectionGuard\Schema;
use ChlodAlejandro\ElectionGuard\Schema\Guardian\GuardianGenerationInfo;

class GuardianAPI extends ElectionGuardAPI {

    /**
     * Creates a guardian.
     * @param \ChlodAlejandro\ElectionGuard\Schema\Guardian\GuardianGenerationInfo $ggi Guardian generation information.
     * @return \ChlodAlejandro\ElectionGuard\Schema\Guardian\ExposedGuardian The guardian, with secret key.
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createGuardian(GuardianGenerationInfo $ggi): Schema\Guardian\ExposedGuardian {
        return $this->execute("guardian", function($url) use ($ggi) {
            $response = $this->client->post($url, [
                "json" => [
                    "id" => $ggi->getId() . "_" . $ggi->getSequenceOrder(),
                    "sequence_order" => $ggi->getSequenceOrder(),
                    "number_of_guardians" => $ggi->getGuardianCount(),
                    "quorum" => $ggi->getQuorum()
                ]
            ]);

            $decodedResponse = json_decode($response->getBody());
            $electionKeyPair = $decodedResponse->election_key_pair;
            $ggi->bumpSequenceOrder();

            return new Schema\Guardian\ExposedGuardian(
                $electionKeyPair->public_key,
                $electionKeyPair->secret_key,
                $electionKeyPair->proof,
                $electionKeyPair->polynomial,
                $decodedResponse->auxiliary_key_pair->public_key,
                $decodedResponse->auxiliary_key_pair->secret_key
            );
        });
    }

}
