<?php

namespace ChlodAlejandro\ElectionGuard\API;

use ChlodAlejandro\ElectionGuard\Schema;
use ChlodAlejandro\ElectionGuard\Schema\ElectionContext;
use ChlodAlejandro\ElectionGuard\Schema\Guardian\Guardian;
use ChlodAlejandro\ElectionGuard\Schema\Manifest\Manifest;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\RequestOptions;
use stdClass;

class GuardianAPI extends ElectionGuardAPI {

    /**
     * Creates a guardian.
     * @param \ChlodAlejandro\ElectionGuard\API\GuardianGenerationInfo $ggi Guardian generation information.
     * @return \GuzzleHttp\Promise\PromiseInterface The guardian.
     * @throws \ChlodAlejandro\ElectionGuard\Error\NoAvailableTargetException
     */
    public function createGuardianAsync(GuardianGenerationInfo $ggi): PromiseInterface {
        return $this->post("guardian", [
            "json" => [
                "id" => $ggi->generateObjectId(),
                "sequence_order" => $ggi->getSequenceOrder(),
                "number_of_guardians" => $ggi->getGuardianCount(),
                "quorum" => $ggi->getQuorum()
            ]
        ])->then(function ($response) use ($ggi) {
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
     * Creates a guardian.
     * @param \ChlodAlejandro\ElectionGuard\API\GuardianGenerationInfo $ggi Guardian generation information.
     * @return \ChlodAlejandro\ElectionGuard\Schema\Guardian\Guardian The guardian.
     */
    public function createGuardian(GuardianGenerationInfo $ggi): Schema\Guardian\Guardian {
        return $this->createGuardianAsync($ggi)->wait();
    }

    /**
     * Decrypt a single guardian's share of a ballot.
     * @param \ChlodAlejandro\ElectionGuard\Schema\ElectionContext $electionContext
     * @param \ChlodAlejandro\ElectionGuard\Schema\Guardian\Guardian $guardian
     * @param \stdClass[] $encryptedBallots
     * @return \GuzzleHttp\Promise\PromiseInterface The decrypted tally share.
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \ChlodAlejandro\ElectionGuard\Error\NoAvailableTargetException
     */
    public function decryptBallotsAsync(
        ElectionContext $electionContext,
        Guardian        $guardian,
        // TODO: Precise types
        array           $encryptedBallots
        // TODO: Precise types
    ): PromiseInterface {
        return $this->post("ballot/decrypt-shares", [
            "json" => [
                "context" => $electionContext->serialize(),
                "guardian" => $guardian->serialize(),
                "encrypted_ballots" => $encryptedBallots
            ]
        ])->then(function ($response) {
            return json_decode($response->getBody());
        });
    }


    /**
     * Decrypt a single guardian's share of a ballot.
     * @param \ChlodAlejandro\ElectionGuard\Schema\ElectionContext $electionContext
     * @param \ChlodAlejandro\ElectionGuard\Schema\Guardian\Guardian $guardian
     * @param \stdClass[] $encryptedBallots
     * @return \stdClass The decrypted tally share.
     * @throws \ChlodAlejandro\ElectionGuard\Error\NoAvailableTargetException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function decryptBallots(
        ElectionContext $electionContext,
        Guardian        $guardian,
        // TODO: Precise types
        array           $encryptedBallots
        // TODO: Precise types
    ): stdClass {
        return $this->decryptBallotsAsync($electionContext, $guardian, $encryptedBallots)->wait();
    }

    /**
     * Decrypt a single guardian's share of a tally.
     * @param \ChlodAlejandro\ElectionGuard\Schema\Manifest\Manifest $manifest
     * @param \ChlodAlejandro\ElectionGuard\Schema\ElectionContext $electionContext
     * @param \ChlodAlejandro\ElectionGuard\Schema\Guardian\Guardian $guardian
     * @param stdClass $tally
     * @return \GuzzleHttp\Promise\PromiseInterface The decrypted tally share.
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \ChlodAlejandro\ElectionGuard\Error\NoAvailableTargetException
     */
    public function decryptTallyShareAsync(
        Manifest               $manifest,
        ElectionContext        $electionContext,
        Guardian               $guardian,
        // TODO: Precise types
        stdClass               $tally
    // TODO: Precise types
    ): PromiseInterface {
        return $this->post("tally/decrypt-share", [
            "json" => [
                "description" => $manifest->serialize(),
                "context" => $electionContext->serialize(),
                "guardian" => $guardian->serialize(),
                "encrypted_tally" => $tally
            ]
        ])->then(function ($response) {
            return json_decode($response->getBody());
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
     * @throws \ChlodAlejandro\ElectionGuard\Error\NoAvailableTargetException
     */
    public function decryptTallyShare(
        Manifest               $manifest,
        ElectionContext        $electionContext,
        Guardian               $guardian,
        // TODO: Precise types
        stdClass               $tally
        // TODO: Precise types
    ): stdClass {
        return $this->decryptTallyShareAsync($manifest, $electionContext, $guardian, $tally)->wait();
    }

}
