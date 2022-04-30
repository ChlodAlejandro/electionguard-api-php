<?php

namespace ChlodAlejandro\ElectionGuard\Error;

use ChlodAlejandro\ElectionGuard\API\ElectionGuardAPI;
use Exception;
use Throwable;

class NoAvailableTargetException extends Exception {

    /**
     * The ElectionGuardAPI that threw the error.
     * @var ElectionGuardAPI
     */
    public $api;
    /**
     * The endpoints involved.
     * @var string
     */
    public $endpoints;

    public function __construct(ElectionGuardAPI $api, Throwable $previous = null) {
        parent::__construct("Could not find an available API endpoint.", 503, $previous);
        $this->api = $api;
        $this->endpoints = $api->getTargets();
    }

}
