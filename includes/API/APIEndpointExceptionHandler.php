<?php
namespace ChlodAlejandro\ElectionGuard\API;

interface APIEndpointExceptionHandler {

    public function __invoke(string $endpointUrl);

}
