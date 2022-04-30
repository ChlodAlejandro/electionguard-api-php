<?php

namespace ChlodAlejandro\ElectionGuard\API;

use ChlodAlejandro\ElectionGuard\Error\InvalidManifestException;
use ChlodAlejandro\ElectionGuard\Schema\ElectionContext;
use ChlodAlejandro\ElectionGuard\Schema\Manifest\Manifest;
use ChlodAlejandro\ElectionGuard\Schema\Manifest\SerializableUtils;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use stdClass;

class MediatorAPI extends ElectionGuardAPI {

    public $seedHash;

    /**
     * Return the constants defined for an election.
     * @return \GuzzleHttp\Promise\PromiseInterface
     * @throws \ChlodAlejandro\ElectionGuard\Error\NoAvailableTargetException
     */
    public function getElectionConstantsAsync(): PromiseInterface {
        return $this->get("election/constants")->then(
            function($response) {
                return json_decode($response->getBody());
            }
        );
    }

    /**
     * Return the constants defined for an election.
     * @return \stdClass
     * @throws \ChlodAlejandro\ElectionGuard\Error\NoAvailableTargetException
     */
    public function getElectionConstants(): stdClass {
        return $this->getElectionConstantsAsync()->wait();
    }

    /**
     * Validate an Election description or manifest for a given election
     * @param Manifest $description The election manifest.
     * @return \GuzzleHttp\Promise\PromiseInterface
     * @throws \ChlodAlejandro\ElectionGuard\Error\NoAvailableTargetException
     */
    public function validateDescriptionAsync(Manifest $description): PromiseInterface {
        return $this->post("election/validate/description", [
            "json" => [
                "description" => $description->serialize()
            ]
        ])->then(function($response) use ($description) {
            $decodedResponse = json_decode($response->getBody());

            return $decodedResponse->success ? true : new InvalidManifestException(
                $response,
                $description,
                $decodedResponse
            );
        });
    }


    /**
     * Validate an Election description or manifest for a given election
     * @param Manifest $description The election manifest.
     * @return bool|\ChlodAlejandro\ElectionGuard\Error\InvalidManifestException
     * @throws \ChlodAlejandro\ElectionGuard\Error\NoAvailableTargetException
     */
    public function validateDescription(Manifest $description) {
        return $this->validateDescriptionAsync($description)->wait();
    }

    /**
     * Combines Guardian public keys to get an election key.
     * @param string[] $publicKeys The election manifest.
     * @return \GuzzleHttp\Promise\PromiseInterface The joint public key.
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \ChlodAlejandro\ElectionGuard\Error\NoAvailableTargetException
     */
    public function combineElectionKeysAsync(array $publicKeys): PromiseInterface {
        return $this->post("key/election/combine", [
            "json" => [
                "election_public_keys" => $publicKeys
            ]
        ])->then(function ($response) {
            $decodedResponse = json_decode($response->getBody());

            return $decodedResponse->joint_key;
        });
    }


    /**
     * Combines Guardian public keys to get an election key.
     * @param string[] $publicKeys The election manifest.
     * @return string The joint public key.
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \ChlodAlejandro\ElectionGuard\Error\NoAvailableTargetException
     */
    public function combineElectionKeys(array $publicKeys): string {
        return $this->combineElectionKeysAsync($publicKeys)->wait();
    }

    /**
     * Constructs an election context from the guardian generation info and the election manifest.
     * @param \ChlodAlejandro\ElectionGuard\Schema\Manifest\Manifest $manifest Election manifest.
     * @param \ChlodAlejandro\ElectionGuard\API\GuardianSetInfo $gsi Guardian set information.
     * @param string $jointKey The joint public key.
     * @return \GuzzleHttp\Promise\PromiseInterface The election context.
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \ChlodAlejandro\ElectionGuard\Error\NoAvailableTargetException
     */
    public function getElectionContextAsync(
        Manifest        $manifest,
        GuardianSetInfo $gsi,
        string          $jointKey
    ): PromiseInterface {
        return $this->post("election/context", [
            "json" => [
                "description" => $manifest->serialize(),
                "elgamal_public_key" => $jointKey,
                "number_of_guardians" => $gsi->getGuardianCount(),
                "quorum" => $gsi->getQuorum()
            ]
        ])->then(function($response) {
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
     * Constructs an election context from the guardian generation info and the election manifest.
     * @param \ChlodAlejandro\ElectionGuard\Schema\Manifest\Manifest $manifest Election manifest.
     * @param \ChlodAlejandro\ElectionGuard\API\GuardianSetInfo $gsi Guardian set information.
     * @param string $jointKey The joint public key.
     * @return \ChlodAlejandro\ElectionGuard\Schema\ElectionContext The election context.
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \ChlodAlejandro\ElectionGuard\Error\NoAvailableTargetException
     */
    public function getElectionContext(
        Manifest        $manifest,
        GuardianSetInfo $gsi,
        string          $jointKey
    ): ElectionContext {
        return $this->getElectionContextAsync($manifest, $gsi, $jointKey)->wait();
    }

    /**
     * Encrypts one or more ballots.
     * @param \ChlodAlejandro\ElectionGuard\Schema\Manifest\Manifest $manifest
     * @param \ChlodAlejandro\ElectionGuard\Schema\ElectionContext $context
     * @param \ChlodAlejandro\ElectionGuard\Schema\Ballot\Ballot[] $ballots
     * @param int|null $seedHash
     * @return \GuzzleHttp\Promise\PromiseInterface The encrypted ballots
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    public function encryptBallotsAsync(
        Manifest $manifest,
        ElectionContext $context,
        array $ballots,
        ?int $seedHash
    ): PromiseInterface {
        if (!isset($seedHash) && !isset($this->seedHash))
            $this->seedHash = random_int(0, PHP_INT_MAX);
        return $this->post("ballot/encrypt", [
            "json" => [
                "description" => $manifest->serialize(),
                "nonce" => random_int(0, PHP_INT_MAX),
                "seed_hash" => $this->seedHash,
                "context" => $context->serialize(),
                "ballots" => SerializableUtils::serializeArray($ballots)
            ]
        ])->then(function($response) {
            $decodedResponse = json_decode($response->getBody());
            $this->seedHash = $decodedResponse->next_seed_hash;

            return $decodedResponse->encrypted_ballots;
        });
    }

    /**
     * Encrypts one or more ballots.
     * @param \ChlodAlejandro\ElectionGuard\Schema\Manifest\Manifest $manifest
     * @param \ChlodAlejandro\ElectionGuard\Schema\ElectionContext $context
     * @param \ChlodAlejandro\ElectionGuard\Schema\Ballot\Ballot[] $ballots
     * @param int|null $seedHash
     * @return stdClass[] The encrypted ballots
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function encryptBallots(
        Manifest $manifest,
        ElectionContext $context,
        array $ballots,
        ?int $seedHash = null
    ): array {
        return $this->encryptBallotsAsync($manifest, $context, $ballots, $seedHash)->wait();
    }

    /**
     * Cast or spoil a ballot.
     * @param bool $cast
     * @param \ChlodAlejandro\ElectionGuard\Schema\Manifest\Manifest $manifest
     * @param \ChlodAlejandro\ElectionGuard\Schema\ElectionContext $context
     * @param stdClass $ballot The encrypted ballot
     * @return \GuzzleHttp\Promise\PromiseInterface The cast/spoiled ballot
     * @throws \ChlodAlejandro\ElectionGuard\Error\NoAvailableTargetException
     */
    private function castOrSpoilBallotAsync(
        bool $cast,
        Manifest $manifest,
        ElectionContext $context,
        // TODO: Precise type
        stdClass $ballot
    ): PromiseInterface {
        return $this->post(
            "ballot/" . ($cast ? "cast" : "spoil"),
            [
                "json" => [
                    "description" => $manifest->serialize(),
                    "context" => $context->serialize(),
                    "ballot" => $ballot
                ]
            ]
        )->then(function($response) {
            return json_decode($response->getBody());
        });
    }

    /**
     * Cast or spoil a ballot.
     * @param bool $cast
     * @param \ChlodAlejandro\ElectionGuard\Schema\Manifest\Manifest $manifest
     * @param \ChlodAlejandro\ElectionGuard\Schema\ElectionContext $context
     * @param stdClass $ballot The encrypted ballot
     * @return stdClass The cast ballot
     * @throws \ChlodAlejandro\ElectionGuard\Error\NoAvailableTargetException
     */
    private function castOrSpoilBallot(
        bool $cast,
        Manifest $manifest,
        ElectionContext $context,
        // TODO: Precise type
        stdClass $ballot
    ): stdClass {
        return $this->castOrSpoilBallotAsync($cast, $manifest, $context, $ballot)->wait();
    }

    /**
     * Cast a ballot.
     * @param \ChlodAlejandro\ElectionGuard\Schema\Manifest\Manifest $manifest
     * @param \ChlodAlejandro\ElectionGuard\Schema\ElectionContext $context
     * @param stdClass $ballot
     * @return \GuzzleHttp\Promise\PromiseInterface The cast ballot
     * @throws \ChlodAlejandro\ElectionGuard\Error\NoAvailableTargetException
     */
    public function castBallotAsync(
        Manifest $manifest,
        ElectionContext $context,
        // TODO: Precise type
        stdClass $ballot
    ): PromiseInterface {
        return $this->castOrSpoilBallotAsync(true, $manifest, $context, $ballot);
    }

    /**
     * Cast a ballot.
     * @param \ChlodAlejandro\ElectionGuard\Schema\Manifest\Manifest $manifest
     * @param \ChlodAlejandro\ElectionGuard\Schema\ElectionContext $context
     * @param stdClass $ballot
     * @return stdClass The cast ballot
     * @throws \ChlodAlejandro\ElectionGuard\Error\NoAvailableTargetException
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
     * @return \GuzzleHttp\Promise\PromiseInterface The spoiled ballot
     * @throws \ChlodAlejandro\ElectionGuard\Error\NoAvailableTargetException
     */
    public function spoilBallotAsync(
        Manifest $manifest,
        ElectionContext $context,
        // TODO: Precise type
        stdClass $ballot
    ): PromiseInterface {
        return $this->castOrSpoilBallotAsync(false, $manifest, $context, $ballot);
    }


    /**
     * Spoil a ballot.
     * @param \ChlodAlejandro\ElectionGuard\Schema\Manifest\Manifest $manifest
     * @param \ChlodAlejandro\ElectionGuard\Schema\ElectionContext $context
     * @param stdClass $ballot
     * @return stdClass The spoiled ballot
     * @throws \ChlodAlejandro\ElectionGuard\Error\NoAvailableTargetException
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
     * @return \GuzzleHttp\Promise\PromiseInterface The tally
     * @throws \ChlodAlejandro\ElectionGuard\Error\NoAvailableTargetException
     */
    public function tallyAsync(
        Manifest $manifest,
        ElectionContext $context,
        // TODO: Precise type
        array $ballots,
        // TODO: Precise type
        stdClass $encryptedTally = null
    // TODO: Precise type
    ): PromiseInterface {
        return $this->post(
            "tally" . (isset($encryptedTally) ? "/append" : ""),
            [
                "json" => array_filter([
                    "description" => $manifest->serialize(),
                    "context" => $context->serialize(),
                    "ballots" => $ballots,
                    "encrypted_tally" => $encryptedTally
                ])
            ]
        )->then(function (ResponseInterface $response) {
            return json_decode($response->getBody());
        });
    }

    /**
     * Start a tally.
     * @param \ChlodAlejandro\ElectionGuard\Schema\Manifest\Manifest $manifest
     * @param \ChlodAlejandro\ElectionGuard\Schema\ElectionContext $context
     * @param stdClass[] $ballots
     * @param stdClass|null $encryptedTally The encrypted tally (null to start a new tally).
     * @return stdClass The tally
     * @throws \ChlodAlejandro\ElectionGuard\Error\NoAvailableTargetException
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
        return $this->tallyAsync($manifest, $context, $ballots, $encryptedTally)->wait();
    }

    /**
     * Decrypt ballots from Guardian tally shares.
     * @param \ChlodAlejandro\ElectionGuard\Schema\ElectionContext $context
     * @param \stdClass[] $encryptedBallots
     * @param \stdClass[] $decryptedBallotShares
     * @return \GuzzleHttp\Promise\PromiseInterface
     * @throws \ChlodAlejandro\ElectionGuard\Error\NoAvailableTargetException
     */
    public function decryptBallotsAsync(
        ElectionContext $context,
        // TODO: Precise type
        array $encryptedBallots,
        // TODO: Precise type
        array $decryptedBallotShares
        // TODO: Precise type
    ): PromiseInterface {
        return $this->post(
            "ballot/decrypt",
            [
                "json" => array_filter([
                    "context" => $context->serialize(),
                    "encrypted_ballots" => $encryptedBallots,
                    "shares" => $decryptedBallotShares
                ])
            ]
        )->then(function (ResponseInterface $response) {
            return json_decode($response->getBody());
        });
    }

    /**
     * Decrypt ballots from Guardian tally shares.
     * @param \ChlodAlejandro\ElectionGuard\Schema\ElectionContext $context
     * @param \stdClass[] $encryptedBallots
     * @param \stdClass[] $decryptedBallotShares
     * @return stdClass
     * @throws \ChlodAlejandro\ElectionGuard\Error\NoAvailableTargetException
     */
    public function decryptBallots(
        ElectionContext $context,
        // TODO: Precise type
        array $encryptedBallots,
        // TODO: Precise type
        array $decryptedBallotShares
        // TODO: Precise type
    ): stdClass {
        return $this->decryptBallotsAsync($context, $encryptedBallots, $decryptedBallotShares)->wait();
    }

    /**
     * Decrypt a tally from Guardian tally shares.
     * @param \ChlodAlejandro\ElectionGuard\Schema\Manifest\Manifest $manifest
     * @param \ChlodAlejandro\ElectionGuard\Schema\ElectionContext $context
     * @param stdClass $tally
     * @param array $decryptedTallyShares
     * @return PromiseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function decryptTallyAsync(
        Manifest $manifest,
        ElectionContext $context,
        // TODO: Precise type
        stdClass $tally,
        // TODO: Precise type
        array $decryptedTallyShares
        // TODO: Precise type
    ): PromiseInterface {
        return $this->post(
            "tally/decrypt",
            [
                "json" => array_filter([
                    "description" => $manifest->serialize(),
                    "context" => $context->serialize(),
                    "encrypted_tally" => $tally,
                    "shares" => $decryptedTallyShares
                ])
            ]
        )->then(function (ResponseInterface $response) {
            return json_decode($response->getBody());
        });
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
        return $this->decryptTallyAsync($manifest, $context, $tally, $decryptedTallyShares)->wait();
    }

    /**
     * Convert tracker from hash to human readable / friendly words
     * @param string $trackerHash The tracker hash
     * @param string $separator The separator to use between words
     * @return PromiseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException|\ChlodAlejandro\ElectionGuard\Error\NoAvailableTargetException
     */
    public function getTrackerWordsAsync(string $trackerHash, string $separator = "-"): PromiseInterface {
        return $this->post("tracker/words", [
            RequestOptions::JSON => [
                "tracker_hash" => $trackerHash,
                "separator" => $separator
            ]
        ])->then(function($response) {
            return json_decode($response->getBody())->tracker_words;
        });
    }

    /**
     * Convert tracker from hash to human readable / friendly words
     * @param string $trackerHash The tracker hash
     * @param string $separator The separator to use between words
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException|\ChlodAlejandro\ElectionGuard\Error\NoAvailableTargetException
     */
    public function getTrackerWords(string $trackerHash, string $separator = "-"): string {
        return $this->getTrackerWordsAsync($trackerHash, $separator)->wait();
    }

}
