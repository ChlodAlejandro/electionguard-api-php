<?php

namespace ChlodAlejandro\ElectionGuard\Schema\Guardian;

use ChlodAlejandro\ElectionGuard\Error\InvalidDefinitionException;
use ChlodAlejandro\ElectionGuard\Schema\ISerializable;
use stdClass;

class Guardian implements ISerializable {

    /** @var string The public key of this guardian. */
    protected $publicKey;
    // TODO: Convert to interface.
    /** @var stdClass Proof of knowledge for this guardian. */
    protected $proof;
    // TODO: Convert to interface.
    /** @var stdClass See https://www.electionguard.vote/overview/Glossary/#election-polynomial. */
    protected $polynomial;

    public function __construct($publicKey, $proof, $polynomial) {
        $this->publicKey = $publicKey;
        $this->proof = $proof;
        $this->polynomial = $polynomial;
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
