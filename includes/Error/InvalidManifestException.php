<?php

namespace ChlodAlejandro\ElectionGuard\Error;

use ChlodAlejandro\ElectionGuard\Schema\Manifest\Manifest;
use GuzzleHttp\Psr7\Response;
use RuntimeException;
use stdClass;

class InvalidManifestException extends RuntimeException {

    /** @var Response The validation HTTP response. */
    public $response;
    /** @var Manifest The manifest with issues. */
    public $manifest;
    /** @var string The message of the validation. */
    public $message;
    /** @var array Validation issues found. */
    public $details;

    public function __construct(
        ?Response $response = null,
        ?Manifest $manifest = null,
        ?stdClass $details = null
    ) {
        $this->response = $response;
        $this->manifest = $manifest;
        $this->message = $details->message ?? 'Invalid manifest.';
        $this->details = $details->details ?? new stdClass();
        parent::__construct($this->message);
    }

}
