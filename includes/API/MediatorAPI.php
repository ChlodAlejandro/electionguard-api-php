<?php

namespace ChlodAlejandro\ElectionGuard\API;

use ChlodAlejandro\ElectionGuard\Error\InvalidManifestException;
use ChlodAlejandro\ElectionGuard\Schema\Manifest\Manifest;

class MediatorAPI extends ElectionGuardAPI {

    /**
     * Return the constants defined for an election.
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getElectionConstants(): array {
        return $this->execute("election/constants", function($url) {
            $response = $this->client->get($url);

            return json_decode($response->getBody(), true);
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

}
