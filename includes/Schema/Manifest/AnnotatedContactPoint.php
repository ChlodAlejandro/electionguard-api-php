<?php

namespace ChlodAlejandro\ElectionGuard\Schema\Manifest;

use ChlodAlejandro\ElectionGuard\Schema\ISerializable;

abstract class AnnotatedContactPoint implements ISerializable {

    /**
     * The annotation for this contact point.
     * @var string
     */
    public $annotation;

    /**
     * The value for this contact point.
     * @var string
     */
    public $value;

    public function getAnnotation(): string {
        return $this->annotation;
    }

    public function setAnnotation(string $annotation): AnnotatedContactPoint {
        $this->annotation = $annotation;

        return $this;
    }

    public function getValue(): string {
        return $this->value;
    }

    public function setValue(string $value): AnnotatedContactPoint {
        $this->value = $value;

        return $this;
    }

    public function __construct(?string $annotation = null, ?string $value = null) {
        if (!empty($annotation))
            $this->annotation = $annotation;
        if (!empty($value))
            $this->value = $value;
    }

    public function serialize(): array {
        return [
            "annotation" => $this->annotation,
            "value" => $this->value
        ];
    }

}

