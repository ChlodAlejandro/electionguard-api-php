<?php declare(strict_types=1);

require_once __DIR__ . "/../vendor/autoload.php";
include_once __DIR__ . "/TestDataHandler.php";

use ChlodAlejandro\ElectionGuard\API\GuardianAPI;
use ChlodAlejandro\ElectionGuard\API\MediatorAPI;

class EndToEndElectionTest extends AsynchronousEndToEndElectionTest {

    public function mediatorAPI(): MediatorAPI {
        return $this->_mediatorAPI[0];
    }

    public function guardianAPI(): GuardianAPI {
        return $this->_guardianAPI[0];
    }

}
