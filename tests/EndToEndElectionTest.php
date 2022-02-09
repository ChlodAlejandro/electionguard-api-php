<?php declare(strict_types=1);

require_once __DIR__ . "/../vendor/autoload.php";
include_once __DIR__ . "/TestDataHandler.php";

use ChlodAlejandro\ElectionGuard\API\GuardianAPI;
use ChlodAlejandro\ElectionGuard\API\MediatorAPI;
use ChlodAlejandro\ElectionGuard\Schema\Guardian\Guardian;
use ChlodAlejandro\ElectionGuard\Schema\Guardian\GuardianGenerationInfo;
use PHPUnit\Framework\TestCase;

final class EndToEndElectionTest extends TestCase {

    /**
     * @var \ChlodAlejandro\ElectionGuard\Schema\Manifest\Manifest
     */
    private $manifest;
    /**
     * @var \ChlodAlejandro\ElectionGuard\API\MediatorAPI
     */
    private $mediatorAPI;
    /**
     * @var \ChlodAlejandro\ElectionGuard\API\GuardianAPI
     */
    private $guardianAPI;

    /**
     * @throws \ChlodAlejandro\ElectionGuard\Error\InvalidDefinitionException
     */
    public function __construct(?string $name = null, array $data = [], $dataName = '') {
        parent::__construct($name, $data, $dataName);

        $this->manifest = TestDataHandler::getManifest();
        $this->mediatorAPI =
            new MediatorAPI(getenv("ELECTIONGUARD_MEDIATOR_HOST") ?? "localhost:8000");
        $this->guardianAPI =
            new GuardianAPI(getenv("ELECTIONGUARD_GUARDIAN_HOST") ?? "localhost:8001");
    }

    /**
     * Performs an end-to-end election.
     * @test
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function test(): void {
        // Validate the description
        $validation = $this->mediatorAPI->validateDescription($this->manifest);
        self::assertTrue($validation);

        // Generate guardians
        $guardianCount = 5;
        $quorum = 3;
        $ggi = new GuardianGenerationInfo("test-election", $guardianCount, $quorum);

        $guardians = [];
        for ($i = 0; $i < $guardianCount; $i++) {
            $guardian = $this->guardianAPI->createGuardian($ggi);

            self::assertIsString($guardian->getPublicKey());
            self::assertIsString($guardian->getSecretKey());
            self::assertInstanceOf(stdClass::class, $guardian->getPolynomial());
            self::assertInstanceOf(stdClass::class, $guardian->getProof());

            $guardians[] = $guardian;
        }

        // Combine to make public election key
        $electionKey = $this->mediatorAPI->combineElectionKeys(array_map(function (Guardian $guardian) {
            return $guardian->getPublicKey();
        }, $guardians));

        self::assertIsString($electionKey);
    }

}
