<?php

namespace ChlodAlejandro\ElectionGuard\Error;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use RuntimeException;

class UnexpectedResponseException extends RuntimeException {

    public $response;

    public function __construct(
        ?string          $message = null,
        ?GuzzleException $previous = null,
        ?Response        $response = null
    ) {
        $this->response = $response;

        parent::__construct(
            $message ?? ($response == null ? null : $response->getReasonPhrase()),
            ($response == null ? null : $response->getStatusCode()),
            $previous
        );
    }

}
