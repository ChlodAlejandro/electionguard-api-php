<?php

namespace ChlodAlejandro\ElectionGuard\Schema\Guardian;

use ChlodAlejandro\ElectionGuard\Error\InvalidDefinitionException;

class ExposedGuardian extends Guardian {

    private $secretKey;
    private $auxiliarySecretKey;
    private $auxiliaryPublicKey;

    public function __construct(
        $publicKey,
        $secretKey,
        $proof,
        $polynomial,
        $auxiliaryPublicKey = null,
        $auxiliarySecretKey = null
    ) {
        parent::__construct($publicKey, $proof, $polynomial);
        $this->secretKey = $secretKey;
        $this->auxiliaryPublicKey = $auxiliaryPublicKey;
        $this->auxiliarySecretKey = $auxiliarySecretKey;
    }

    /**
     * @return mixed
     */
    public function getSecretKey() {
        return $this->secretKey;
    }

    public function validate(): bool {
        parent::validate();
        if (!isset($this->secretKey))
            throw new InvalidDefinitionException("Guardian secret key is required");

        return true;
    }

    public function serialize(): array {
        return array_merge(parent::serialize(), [
            "secret_key" => $this->secretKey,
            "auxiliary_secret_key" => $this->auxiliarySecretKey,
            "auxiliary_public_key" => $this->auxiliaryPublicKey
        ]);
    }

}
