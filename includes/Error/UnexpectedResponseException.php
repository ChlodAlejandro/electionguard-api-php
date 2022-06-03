<?php

namespace ChlodAlejandro\ElectionGuard\Error;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Response;
use RuntimeException;

class UnexpectedResponseException extends RuntimeException {

    /** @var \GuzzleHttp\Exception\BadResponseException */
    public $previous;
    /** @var \Psr\Http\Message\RequestInterface|null */
    public $request;
    /** @var \GuzzleHttp\Psr7\Response|null */
    public $response;

    public function __construct(
        ?string          $message = null,
        ?GuzzleException $previous = null,
        ?Response        $response = null
    ) {
        if ($previous instanceof ServerException || $previous instanceof ClientException) {
            $this->request = $previous->getRequest();
        }
        $this->response = $response;

        parent::__construct(
            $message ?? ($response == null ? null : $response->getReasonPhrase()),
            ($response == null ? null : $response->getStatusCode()),
            $previous
        );
    }

}
