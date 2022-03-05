<?php

namespace ChlodAlejandro\ElectionGuard\API;

use ChlodAlejandro\ElectionGuard\Error\UnexpectedResponseException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;

class ElectionGuardAPI {

    /**
     * An array of API endpoint roots, which are randomly selected from whenever
     * a request is to be made.
     * @var string[]
     */
    private $endpoints;
    /**
     * Guzzle HTTP client for this mediator handler.
     * @var \GuzzleHttp\Client
     */
    protected $client;

    /**
     * Creates a Mediator endpoint instance.
     * @param string[]|string $endpoints
     */
    public function __construct($endpoints, array $clientOptions = []) {
        if (is_array($endpoints))
            $this->endpoints = $endpoints;
        else
            $this->endpoints = [$endpoints];

        $this->client = new Client(array_merge_recursive(
            [
                "defaults" => [
                    "headers" => [
                        "User-Agent" => "ElectionGuardPHP/1.0"
                    ]
                ]
            ], $clientOptions
        ));
    }

    /**
     * Pick a random endpoint root and get the URL to a specific endpoint.
     * @param ?string $endpoint The endpoint to choose.
     * @return string
     */
    protected function pickEndpoint(?string $endpoint): string {
        return $this->endpoints[array_rand($this->endpoints)]
            . ($endpoint ? "/api/v1/" . $endpoint : "");
    }

    /**
     * @param string $endpoint
     * @param callable $callback
     * @param callable|null $failureCallback
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    function execute(
        string                       $endpoint,
        callable          $callback,
        ?callable $failureCallback = null
    ) {
        try {
            return $callback($this->pickEndpoint($endpoint));
        } catch (GuzzleException $e) {
            if (isset($failureCallback)) {
                return $failureCallback($e);
            } else if ($e instanceof BadResponseException) {
                $response = $e->getResponse();
                throw new UnexpectedResponseException(
                    "Failed to parse response from mediator.",
                    $e,
                    $response
                );
            } else {
                throw $e;
            }
        }
    }

    /**
     * Check if the API can be pinged.
     * @param int $timeout The time to wait for the response.
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function ping(int $timeout = 1): bool {
        return $this->execute("ping", function($url) use ($timeout) {
            $response = $this->client->get($url, [
                'timeout' => $timeout,
                'connect_timeout' => $timeout,
            ]);
            $decodedResponse = json_decode($response->getBody());

            return $decodedResponse === "pong";
        });
    }

}
