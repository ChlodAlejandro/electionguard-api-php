<?php

namespace ChlodAlejandro\ElectionGuard\API;

interface APIEndpointExecutor {

    public function __invoke(string $endpointUrl);

}
