<?php

namespace ChlodAlejandro\ElectionGuard\API;

use ChlodAlejandro\ElectionGuard\Error\NoAvailableTargetException;
use ChlodAlejandro\ElectionGuard\Error\UnexpectedResponseException;
use ChlodAlejandro\ElectionGuard\Utilities;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\Utils;
use Throwable;

class ElectionGuardAPI {

    /**
     * Relative path to a specific version of the endpoint.
     * Use leading slashes. Do not use trailing slashes.
     */
    public const apiPath = "/api/v1";

    /**
     * Skip inaccessible endpoints when checking for endpoint latencies.
     */
    public const SKIP_INACCESSIBLE = 1;
    /**
     * Throw on inaccessible endpoints when checking for endpoint latencies.
     */
    public const THROW_ON_INACCESSIBLE = 2;

    /**
     * An array of API targets available to this API manager.
     * @var string[]
     */
    private $targets;
    /**
     * Guzzle HTTP client for this mediator handler.
     * @var \GuzzleHttp\Client
     */
    protected $client;

    /**
     * The latencies of each target. Gathered through {@link ElectionGuardAPI::getTargetLatencies()}.
     * @var array
     */
    public $endpointLatencies;

    /**
     * Creates a Mediator endpoint instance.
     * @param string[]|string $endpoints
     */
    public function __construct($endpoints, array $clientOptions = []) {
        if (is_array($endpoints))
            $this->targets = $endpoints;
        else
            $this->targets = [$endpoints];

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
     * @return string[]
     */
    public function getTargets(): array {
        return $this->targets;
    }

    /**
     * Gets the normalized URL for a target and endpoint. This ensures that the
     * URL has no trailing slashes and that query parameters were preserved.
     *
     * @param string $target The target to get the URL for.
     * @return string The URL of the endpoint.
     */
    public function getTargetUrl(string $target, string $endpoint = ""): string {
        $url = parse_url($target);
        $sanitizedPath = ltrim($endpoint, "/");
        $url["path"] = empty($url["path"])
            ? ElectionGuardAPI::apiPath . '/' . $sanitizedPath
            : rtrim($url["path"], "/") . ElectionGuardAPI::apiPath . '/' . $sanitizedPath;
        return Utilities::unparse_url($url);
    }

    /**
     * Gets the average ping for each target and returns all the targets
     * and their resulting pings in an associative array, sorted from lowest
     * to highest ping in milliseconds. If a target cannot be pinged and
     * $throwOnError is set to false, the endpoint will have a ping of INF.
     *
     * @param int $options Options for latency checking. Valid values are
     *     {@link ElectionGuardAPI::SKIP_INACCESSIBLE} and {@link ElectionGuardAPI::THROW_ON_INACCESSIBLE}.
     * @return array<string, int> An array of target-ping tuples.
     * @throws UnexpectedResponseException If a target can't be pinged and $throwOnError is set to false.
     */
    public function getTargetLatencies(int $options = 0): array {
        $pings = [];
        $pingPromises = [];
        foreach ($this->targets as $target) {
            $startTime = microtime(true);
            $pingPromises[] = $this->client->getAsync($this->getTargetUrl($target, "ping"))
                ->then(
                    function () use (&$pings, $target, $startTime) {
                        $pings[$target] = (int) ((microtime(true) - $startTime) * 1000);
                    },
                    function () use (&$pings, $target, $options) {
                        if ($options & ElectionGuardAPI::THROW_ON_INACCESSIBLE) {
                            throw new UnexpectedResponseException(
                                "Failed to ping endpoint: " . $target
                            );
                        } else {
                            $pings[$target] = INF;
                        }
                    }
                );
        }
        Utils::all($pingPromises)->wait();

        uasort($pings, function ($a, $b) {
            return $a - $b;
        });
        if ($options & ElectionGuardAPI::SKIP_INACCESSIBLE) {
            return array_filter($pings, function ($ping) {
                return $ping !== INF;
            });
        } else {
            return $this->endpointLatencies = $pings;
        }
    }

    /**
     * Pick a random target and get the URL to a specific endpoint.
     * @param ?string $endpoint The endpoint to choose.
     * @return string
     * @throws \ChlodAlejandro\ElectionGuard\Error\NoAvailableTargetException
     */
    protected function pickTarget(?string $endpoint): string {
        if ($this->endpointLatencies == null) {
            $this->getTargetLatencies();
        }
        $availableEndpoints = array_filter($this->endpointLatencies, function ($ping) {
            return $ping !== INF;
        });
        if (count($availableEndpoints) == 0) {
            throw new NoAvailableTargetException($this);
        }
        return $this->getTargetUrl(
            array_key_first($availableEndpoints),
            $endpoint
        );
    }

    /**
     * @throws \Throwable
     */
    private function processRequestException(Throwable $e): void {
        if ($e instanceof BadResponseException) {
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

    /**
     * @param string $endpoint The endpoint to access
     * @param array $requestOptions The request options for this request
     * @return \GuzzleHttp\Promise\PromiseInterface
     * @throws \ChlodAlejandro\ElectionGuard\Error\NoAvailableTargetException
     */
    function get(string $endpoint, array $requestOptions = []): PromiseInterface {
        return $this->client->getAsync($this->pickTarget($endpoint), $requestOptions)
            ->then(null, function ($e) { $this->processRequestException($e); });
    }

    /**
     * @param string $endpoint The endpoint to access
     * @param array $requestOptions The request options for this request
     * @return \GuzzleHttp\Promise\PromiseInterface
     * @throws \ChlodAlejandro\ElectionGuard\Error\NoAvailableTargetException
     */
    function post(string $endpoint, array $requestOptions = []): PromiseInterface {
        return $this->client->postAsync($this->pickTarget($endpoint), $requestOptions)
            ->then(null, function ($e) { $this->processRequestException($e); });
    }

}
