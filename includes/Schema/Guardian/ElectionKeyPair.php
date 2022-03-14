<?php

namespace ChlodAlejandro\ElectionGuard\Schema\Guardian;

use ChlodAlejandro\ElectionGuard\Error\InvalidDefinitionException;
use ChlodAlejandro\ElectionGuard\Schema\ISerializable;
use stdClass;

class ElectionKeyPair implements ISerializable {

    /** @var string The public key of this guardian. */
    private $publicKey;
    /** @var string|null The secret key of this guardian. */
    private $secretKey;
    // TODO: Convert to interface.
    /** @var stdClass Proof of knowledge for this guardian. */
    private $proof;
    // TODO: Convert to interface.
    /** @var stdClass See https://www.electionguard.vote/overview/Glossary/#election-polynomial. */
    private $polynomial;

    /**
     * @param string|\stdClass|array $json
     * @return \ChlodAlejandro\ElectionGuard\Schema\Guardian\ElectionKeyPair
     */
    public static function fromJson($json): ElectionKeyPair {
        $data = is_string($json)
            ? json_decode($json, true)
            : ($json instanceof stdClass ? $json : json_decode(json_encode($json), false));

        return new ElectionKeyPair(
            $data->proof,
            $data->polynomial,
            $data->public_key,
            $data->secret_key ?? null,
        );
    }

    public function __construct($proof, $polynomial, $publicKey, $secretKey = null) {
        $this->publicKey = $publicKey;
        $this->proof = $proof;
        $this->polynomial = $polynomial;
        $this->secretKey = $secretKey;
    }

    public function isExposed(): bool {
        return !empty($this->secretKey);
    }

    public function getSecretKey() {
        return $this->secretKey;
    }

    public function getPublicKey(): string {
        return $this->publicKey;
    }

    public function getProof(): stdClass {
        return $this->proof;
    }

    public function getPolynomial(): stdClass {
        return $this->polynomial;
    }

    public function serialize(): array {
        return array_filter([
            "public_key" => $this->publicKey,
            "secret_key" => $this->secretKey,
            "proof" => $this->proof,
            "polynomial" => $this->polynomial
        ]);
    }

    public function validate(): bool {
        if (!isset($this->publicKey))
            throw new InvalidDefinitionException("Guardian must have a public key.");
        if (!isset($this->proof))
            throw new InvalidDefinitionException("Guardian must have proofs.");
        if (!isset($this->polynomial))
            throw new InvalidDefinitionException("Guardian must have polynomial construction data.");

        return true;
    }

}
