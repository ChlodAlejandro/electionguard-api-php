<?php

namespace ChlodAlejandro\ElectionGuard\API;

use ChlodAlejandro\ElectionGuard\Error\InvalidManifestException;
use ChlodAlejandro\ElectionGuard\Schema\ElectionContext;
use ChlodAlejandro\ElectionGuard\Schema\Manifest\Manifest;
use ChlodAlejandro\ElectionGuard\Schema\Manifest\SerializableUtils;
use GuzzleHttp\RequestOptions;
use stdClass;

class MediatorAPI extends ElectionGuardAPI {

    private $seedHash;

    /**
     * Return the constants defined for an election.
     * @return \stdClass
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getElectionConstants(): stdClass {
        return $this->execute("election/constants", function($url) {
            $response = $this->client->get($url);

            return json_decode($response->getBody());
        });
    }

    /**
     * Validate an Election description or manifest for a given election
     * @param Manifest $description The election manifest.
     * @return bool|\ChlodAlejandro\ElectionGuard\Error\InvalidManifestException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function validateDescription(Manifest $description) {
        return $this->execute("election/validate/description", function($url) use ($description) {
            $response = $this->client->post($url, [
                "json" => [
                    "description" => $description->serialize()
                ]
            ]);
            $decodedResponse = json_decode($response->getBody());

            return $decodedResponse->success ? true : new InvalidManifestException(
                $response,
                $description,
                $decodedResponse
            );
        });
    }

    /**
     * Combines Guardian public keys to get an election key.
     * @param string[] $publicKeys The election manifest.
     * @return string The joint public key.
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function combineElectionKeys(array $publicKeys): string {
        return $this->execute("key/election/combine", function($url) use ($publicKeys) {
            $response = $this->client->post($url, [
                "json" => [
                    "election_public_keys" => $publicKeys
                ]
            ]);
            $decodedResponse = json_decode($response->getBody());

            return $decodedResponse->joint_key;
        });
    }

    /**
     * Constructs an election context from the guardian generation info and the election manifest.
     * @param \ChlodAlejandro\ElectionGuard\Schema\Manifest\Manifest $manifest Election manifest.
     * @param \ChlodAlejandro\ElectionGuard\API\GuardianSetInfo $gsi Guardian set information.
     * @param string $jointKey The joint public key.
     * @return \ChlodAlejandro\ElectionGuard\Schema\ElectionContext The election context.
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getElectionContext(
        Manifest        $manifest,
        GuardianSetInfo $gsi,
        string          $jointKey
    ): ElectionContext {
        return $this->execute("election/context", function($url) use ($manifest, $gsi, $jointKey) {
            $response = $this->client->post($url, [
                "json" => [
                    "description" => $manifest->serialize(),
                    "elgamal_public_key" => $jointKey,
                    "number_of_guardians" => $gsi->getGuardianCount(),
                    "quorum" => $gsi->getQuorum()
                ]
            ]);
            $decodedResponse = json_decode($response->getBody());

            return new ElectionContext(
                $decodedResponse->crypto_base_hash,
                $decodedResponse->crypto_extended_base_hash,
                $decodedResponse->description_hash,
                $decodedResponse->elgamal_public_key,
                $decodedResponse->number_of_guardians,
                $decodedResponse->quorum,
            );
        });
    }

    /**
     * Encrypts one or more ballots.
     * @param \ChlodAlejandro\ElectionGuard\Schema\Manifest\Manifest $manifest
     * @param \ChlodAlejandro\ElectionGuard\Schema\ElectionContext $context
     * @param \ChlodAlejandro\ElectionGuard\Schema\Ballot\Ballot[] $ballots
     * @return stdClass[] The encrypted ballots
     * @throws \Exception|\GuzzleHttp\Exception\GuzzleException
     */
    public function encryptBallots(
        Manifest $manifest,
        ElectionContext $context,
        array $ballots
    ): array {
        if (!isset($this->seedHash))
            $this->seedHash = random_int(0, PHP_INT_MAX);
        return $this->execute("ballot/encrypt", function($url) use ($manifest, $context, $ballots) {
            $response = $this->client->post($url, [
                "json" => [
                    "description" => $manifest->serialize(),
                    "nonce" => random_int(0, PHP_INT_MAX),
                    "seed_hash" => $this->seedHash,
                    "context" => $context->serialize(),
                    "ballots" => SerializableUtils::serializeArray($ballots)
                ]
            ]);
            $decodedResponse = json_decode($response->getBody());
            $this->seedHash = $decodedResponse->next_seed_hash;

            return $decodedResponse->encrypted_ballots;
        });
    }

    /**
     * Cast or spoil a ballot.
     * @param bool $cast
     * @param \ChlodAlejandro\ElectionGuard\Schema\Manifest\Manifest $manifest
     * @param \ChlodAlejandro\ElectionGuard\Schema\ElectionContext $context
     * @param stdClass $ballot The encrypted ballot
     * @return stdClass The cast ballot
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function castOrSpoilBallot(
        bool $cast,
        Manifest $manifest,
        ElectionContext $context,
        // TODO: Precise type
        stdClass $ballot
    ): stdClass {
        return $this->execute(
            "ballot/" . ($cast ? "cast" : "spoil"),
            function($url) use ($manifest, $context, $ballot) {
                $response = $this->client->post($url, [
                    "json" => [
                        "description" => $manifest->serialize(),
                        "context" => $context->serialize(),
                        "ballot" => $ballot
                    ]
                ]);

                return json_decode($response->getBody());
            }
        );
    }

    /**
     * Cast a ballot.
     * @param \ChlodAlejandro\ElectionGuard\Schema\Manifest\Manifest $manifest
     * @param \ChlodAlejandro\ElectionGuard\Schema\ElectionContext $context
     * @param stdClass $ballot
     * @return stdClass The cast ballot
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function castBallot(
        Manifest $manifest,
        ElectionContext $context,
        // TODO: Precise type
        stdClass $ballot
    ): stdClass {
        return $this->castOrSpoilBallot(true, $manifest, $context, $ballot);
    }

    /**
     * Spoil a ballot.
     * @param \ChlodAlejandro\ElectionGuard\Schema\Manifest\Manifest $manifest
     * @param \ChlodAlejandro\ElectionGuard\Schema\ElectionContext $context
     * @param stdClass $ballot
     * @return stdClass The spoiled ballot
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function spoilBallot(
        Manifest $manifest,
        ElectionContext $context,
        // TODO: Precise type
        stdClass $ballot
    ): stdClass {
        return $this->castOrSpoilBallot(false, $manifest, $context, $ballot);
    }

    /**
     * Start a tally.
     * @param \ChlodAlejandro\ElectionGuard\Schema\Manifest\Manifest $manifest
     * @param \ChlodAlejandro\ElectionGuard\Schema\ElectionContext $context
     * @param stdClass[] $ballots
     * @param stdClass|null $encryptedTally The encrypted tally (null to start a new tally).
     * @return stdClass The tally
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function tally(
        Manifest $manifest,
        ElectionContext $context,
        // TODO: Precise type
        array $ballots,
        // TODO: Precise type
        stdClass $encryptedTally = null
    // TODO: Precise type
    ): stdClass {
        return $this->execute(
            "tally" . (isset($encryptedTally) ? "/append" : ""),
            function($url) use ($manifest, $context, $ballots, $encryptedTally) {
                $response = $this->client->post($url, [
                    "json" => array_filter([
                        "description" => $manifest->serialize(),
                        "context" => $context->serialize(),
                        "ballots" => $ballots,
                        "encrypted_tally" => $encryptedTally
                    ])
                ]);

                return json_decode($response->getBody());
            }
        );
    }

    /**
     * Decrypt ballots from Guardian tally shares.
     * @param \ChlodAlejandro\ElectionGuard\Schema\ElectionContext $context
     * @param \stdClass[] $encryptedBallots
     * @param \stdClass[] $decryptedBallotShares
     * @return stdClass
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function decryptBallots(
        ElectionContext $context,
        // TODO: Precise type
        array $encryptedBallots,
        // TODO: Precise type
        array $decryptedBallotShares
        // TODO: Precise type
    ): stdClass {
        return $this->execute(
            "ballot/decrypt",
            function($url) use ($context, $encryptedBallots, $decryptedBallotShares) {
                $response = $this->client->post($url, [
                    "json" => array_filter([
                        "context" => $context->serialize(),
                        "encrypted_ballots" => $encryptedBallots,
                        "shares" => $decryptedBallotShares
                    ])
                ]);

                return json_decode($response->getBody());
            }
        );
    }

    /**
     * Decrypt a tally from Guardian tally shares.
     * @param \ChlodAlejandro\ElectionGuard\Schema\Manifest\Manifest $manifest
     * @param \ChlodAlejandro\ElectionGuard\Schema\ElectionContext $context
     * @param stdClass $tally
     * @param array $decryptedTallyShares
     * @return stdClass
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function decryptTally(
        Manifest $manifest,
        ElectionContext $context,
        // TODO: Precise type
        stdClass $tally,
        // TODO: Precise type
        array $decryptedTallyShares
        // TODO: Precise type
    ): stdClass {
        return $this->execute(
            "tally/decrypt",
            function($url) use ($manifest, $context, $tally, $decryptedTallyShares) {
                $response = $this->client->post($url, [
                    "json" => array_filter([
                        "description" => $manifest->serialize(),
                        "context" => $context->serialize(),
                        "encrypted_tally" => $tally,
                        "shares" => $decryptedTallyShares
                    ])
                ]);

                return json_decode($response->getBody());
            }
        );
    }

    /**
     * Convert tracker from hash to human readable / friendly words
     * @param string $trackerHash The tracker hash
     * @param string $separator The separator to use between words
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getTrackWords(string $trackerHash, string $separator = "-"): string {
        return $this->execute("election/constants", function($url) use ($trackerHash, $separator) {
            $response = $this->client->post($url, [
                RequestOptions::JSON => [
                    "tracker_hash" => $trackerHash,
                    "separator" => $separator
                ]
            ]);

            return json_decode($response->getBody())->tracker_words;
        });
    }

}
