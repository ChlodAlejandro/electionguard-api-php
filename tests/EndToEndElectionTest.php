<?php declare(strict_types=1);

require_once __DIR__ . "/../vendor/autoload.php";
include_once __DIR__ . "/TestDataHandler.php";

use ChlodAlejandro\ElectionGuard\API\GuardianAPI;
use ChlodAlejandro\ElectionGuard\API\MediatorAPI;

class EndToEndElectionTest extends AsynchronousEndToEndElectionTest {

    public function mediatorAPI(): MediatorAPI {
        return is_array($this->mediatorAPI) ? $this->mediatorAPI[0] : $this->mediatorAPI;
    }

    public function guardianAPI(): GuardianAPI {
        return is_array($this->guardianAPI) ? $this->guardianAPI[0] : $this->guardianAPI;
    }

}
