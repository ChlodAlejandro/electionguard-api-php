<?php

namespace ChlodAlejandro\ElectionGuard\Schema\Guardian;

use ChlodAlejandro\ElectionGuard\Error\InvalidDefinitionException;
use ChlodAlejandro\ElectionGuard\Schema\ISerializable;
use stdClass;

class AuxiliaryKeyPair implements ISerializable {

    /** @var string */
    public $publicKey;
    /** @var string */
    public $secretKey;

    /**
     * @param string|\stdClass|array $json
     * @return \ChlodAlejandro\ElectionGuard\Schema\Guardian\AuxiliaryKeyPair
     */
    public static function fromJson($json): AuxiliaryKeyPair {
        $data = is_string($json)
            ? json_decode($json, false)
            : ($json instanceof stdClass ? $json : json_decode(json_encode($json), false));

        return new AuxiliaryKeyPair(
            $data->public_key,
            $data->secret_key
        );
    }

    public function __construct($publicKey, $secretKey) {
        $this->publicKey = $publicKey;
        $this->secretKey = $secretKey;
    }

    /**
     * @return string
     */
    public function getPublicKey(): string {
        return $this->publicKey;
    }

    /**
     * @return string
     */
    public function getSecretKey(): string {
        return $this->secretKey;
    }

    public function serialize(): array {
        return [
            "public_key" => $this->publicKey,
            "secret_key" => $this->secretKey
        ];
    }

    public function validate(): bool {
        if (!isset($this->publicKey))
            throw new InvalidDefinitionException("Auxiliary public key is null.");
        if (!isset($this->secretKey))
            throw new InvalidDefinitionException("Auxiliary secret/private key is null.");

        return true;
    }

}
