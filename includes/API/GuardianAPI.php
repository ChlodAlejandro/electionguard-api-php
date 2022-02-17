<?php

namespace ChlodAlejandro\ElectionGuard\API;

use ChlodAlejandro\ElectionGuard\Schema;
use ChlodAlejandro\ElectionGuard\Schema\ElectionContext;
use ChlodAlejandro\ElectionGuard\Schema\Guardian\Guardian;
use ChlodAlejandro\ElectionGuard\Schema\Manifest\Manifest;
use stdClass;

class GuardianAPI extends ElectionGuardAPI {

    /**
     * Creates a guardian.
     * @param \ChlodAlejandro\ElectionGuard\API\GuardianGenerationInfo $ggi Guardian generation information.
     * @return \ChlodAlejandro\ElectionGuard\Schema\Guardian\Guardian The guardian.
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createGuardian(GuardianGenerationInfo $ggi): Schema\Guardian\Guardian {
        return $this->execute("guardian", function($url) use ($ggi) {
            $response = $this->client->post($url, [
                "json" => [
                    "id" => $ggi->generateObjectId(),
                    "sequence_order" => $ggi->getSequenceOrder(),
                    "number_of_guardians" => $ggi->getGuardianCount(),
                    "quorum" => $ggi->getQuorum()
                ]
            ]);

            $decodedResponse = json_decode($response->getBody());
            $electionKeyPair = $decodedResponse->election_key_pair;
            $ggi->bumpSequenceOrder();

            return new Schema\Guardian\Guardian(
                $ggi,
                new Schema\Guardian\ElectionKeyPair(
                    $electionKeyPair->proof,
                    $electionKeyPair->polynomial,
                    $electionKeyPair->public_key,
                    $electionKeyPair->secret_key
                ),
                new Schema\Guardian\AuxiliaryKeyPair(
                    $decodedResponse->auxiliary_key_pair->public_key,
                    $decodedResponse->auxiliary_key_pair->secret_key
                )
            );
        });
    }

    /**
     * Decrypt a single guardian's share of a tally.
     * @param \ChlodAlejandro\ElectionGuard\Schema\Manifest\Manifest $manifest
     * @param \ChlodAlejandro\ElectionGuard\Schema\ElectionContext $electionContext
     * @param \ChlodAlejandro\ElectionGuard\Schema\Guardian\Guardian $guardian
     * @param stdClass $tally
     * @return stdClass The decrypted tally share.
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function decryptTallyShare(
        Manifest               $manifest,
        ElectionContext        $electionContext,
        Guardian               $guardian,
        // TODO: Precise types
        stdClass               $tally
    // TODO: Precise types
    ): stdClass {
        return $this->execute(
            "tally/decrypt-share",
            function($url) use ($manifest, $electionContext, $guardian, $tally) {
                $response = $this->client->post($url, [
                    "json" => [
                        "description" => $manifest->serialize(),
                        "context" => $electionContext->serialize(),
                        "guardian" => $guardian->serialize(),
                        "encrypted_tally" => $tally
                    ]
                ]);

                return json_decode($response->getBody());
            }
        );
    }

}
