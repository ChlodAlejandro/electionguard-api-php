<?php

namespace ChlodAlejandro\ElectionGuard\Schema;

use ChlodAlejandro\ElectionGuard\Error\InvalidDefinitionException;

class ElectionContext implements ISerializable {

    /** @var string */
    private $cryptoBaseHash;
    /** @var string */
    private $cryptoExtendedBaseHash;
    /** @var string */
    private $descriptionHash;
    /** @var string */
    private $elgamalPublicKey;
    /** @var int */
    private $guardianCount;
    /** @var int */
    private $quorum;

    /**
     * @return string
     */
    public function getCryptoBaseHash(): string {
        return $this->cryptoBaseHash;
    }

    /**
     * @return string
     */
    public function getCryptoExtendedBaseHash(): string {
        return $this->cryptoExtendedBaseHash;
    }

    /**
     * @return string
     */
    public function getDescriptionHash(): string {
        return $this->descriptionHash;
    }

    /**
     * @return string
     */
    public function getElgamalPublicKey(): string {
        return $this->elgamalPublicKey;
    }

    /**
     * @return int
     */
    public function getGuardianCount(): int {
        return $this->guardianCount;
    }

    /**
     * @return int
     */
    public function getQuorum(): int {
        return $this->quorum;
    }

    public function __construct(
        $cryptoBaseHash,
        $cryptoExtendedBaseHash,
        $descriptionHash,
        $elgamalPublicKey,
        $guardianCount,
        $quorum
    ) {
        $this->cryptoBaseHash = $cryptoBaseHash;
        $this->cryptoExtendedBaseHash = $cryptoExtendedBaseHash;
        $this->descriptionHash = $descriptionHash;
        $this->elgamalPublicKey = $elgamalPublicKey;
        $this->guardianCount = $guardianCount;
        $this->quorum = $quorum;
    }

    public function validate(): bool {
        if (!isset($this->cryptoBaseHash))
            throw new InvalidDefinitionException("cryptoBaseHash is not set");
        if (!isset($this->cryptoExtendedBaseHash))
            throw new InvalidDefinitionException("cryptoExtendedBaseHash is not set");
        if (!isset($this->descriptionHash))
            throw new InvalidDefinitionException("descriptionHash is not set");
        if (!isset($this->elgamalPublicKey))
            throw new InvalidDefinitionException("elgamalPublicKey is not set");
        if (!isset($this->guardianCount))
            throw new InvalidDefinitionException("guardianCount is not set");
        if (!isset($this->quorum))
            throw new InvalidDefinitionException("quorum is not set");

        return true;
    }

    public function serialize(): array {
        return array_filter([
            "crypto_base_hash" => $this->cryptoBaseHash,
            "crypto_extended_base_hash" => $this->cryptoExtendedBaseHash,
            "description_hash" => $this->descriptionHash,
            "elgamal_public_key" => $this->elgamalPublicKey,
            "number_of_guardians" => $this->guardianCount,
            "quorum" => $this->quorum
        ]);
    }

}
