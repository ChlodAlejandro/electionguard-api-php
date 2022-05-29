<?php declare(strict_types=1);

require_once __DIR__ . "/../vendor/autoload.php";
include_once __DIR__ . "/TestDataHandler.php";

use ChlodAlejandro\ElectionGuard\API\GuardianAPI;
use ChlodAlejandro\ElectionGuard\API\GuardianGenerationInfo;
use ChlodAlejandro\ElectionGuard\API\MediatorAPI;
use ChlodAlejandro\ElectionGuard\Error\UnexpectedResponseException;
use ChlodAlejandro\ElectionGuard\Schema\Guardian\Guardian;
use GuzzleHttp\Promise\Utils;
use PHPUnit\Framework\TestCase;

class AsynchronousEndToEndElectionTest extends TestCase {

    /**
     * Ballots to be generated per ballot style.
     * @var int
     */
    protected $perStyle = 50;
    /**
     * @var \ChlodAlejandro\ElectionGuard\Schema\Manifest\Manifest
     */
    protected $manifest;
    /**
     * @var \ChlodAlejandro\ElectionGuard\API\MediatorAPI
     */
    protected $mediatorAPI;
    /**
     * @var \ChlodAlejandro\ElectionGuard\API\GuardianAPI
     */
    protected $guardianAPI;

    /**
     * @throws \ChlodAlejandro\ElectionGuard\Error\InvalidDefinitionException
     */
    public function __construct(?string $name = null, array $data = [], $dataName = '') {
        parent::__construct($name, $data, $dataName);

        $this->manifest = TestDataHandler::getManifest();
        if (getenv("ELECTIONGUARD_MEDIATOR_HOST") != null) {
            $hosts = array_filter(explode(";", getenv("ELECTIONGUARD_MEDIATOR_HOST")));
            $this->mediatorAPI = new MediatorAPI(empty($hosts) ? "http://localhost:8001" : $hosts);
        } else {
            $this->mediatorAPI = new MediatorAPI($hosts ?? "http://localhost:8001");
        }

        if (getenv("ELECTIONGUARD_GUARDIAN_HOST") != null) {
            $hosts = array_filter(explode(";", getenv("ELECTIONGUARD_GUARDIAN_HOST")));
            $this->guardianAPI = new GuardianAPI(empty($hosts) ? "http://localhost:8001" : $hosts);
        } else {
            $this->guardianAPI = new GuardianAPI($hosts ?? "http://localhost:8001");
        }
    }

    /**
     * @test
     * @medium
     * @return void
     */
    public function endpointTests() {
        $timings = $this->mediatorAPI->getTargetLatencies();
        $this->assertSameSize($this->mediatorAPI->getTargets(), $timings);
        var_dump($timings);
    }

    /**
     * Performs an end-to-end election.
     * @test
     * @large
     * @return void
     */
    public function test(): void {
        try {
            $constants = $this->mediatorAPI->getElectionConstants();

            echo "[i] Validate the description" . PHP_EOL;
            $validation = $this->mediatorAPI->validateDescription($this->manifest);
            self::assertTrue($validation);

            echo "[i] Generate guardians" . PHP_EOL;
            $guardianCount = 5;
            $quorum = 3;
            $ggi = new GuardianGenerationInfo("test-election", $guardianCount, $quorum);

            /** @var \ChlodAlejandro\ElectionGuard\Schema\Guardian\Guardian[] $guardians */
            $guardians = [];
            $ggPromises = [];
            for ($i = 0; $i < $guardianCount; $i++) {
                $ggPromises[] = $this->guardianAPI->createGuardianAsync($ggi)->then(
                    function ($guardian) use (&$guardians) {
                        self::assertIsString($guardian->getAuxiliaryKeyPair()->getPublicKey());
                        self::assertIsString($guardian->getAuxiliaryKeyPair()->getSecretKey());
                        self::assertIsString($guardian->getElectionKeyPair()->getPublicKey());
                        self::assertIsString($guardian->getElectionKeyPair()->getSecretKey());
                        self::assertInstanceOf(stdClass::class, $guardian->getElectionKeyPair()->getPolynomial());
                        self::assertInstanceOf(stdClass::class, $guardian->getElectionKeyPair()->getProof());
                        $guardian->validate();

                        $guardians[] = $guardian;
                    }
                );
            }
            Utils::all($ggPromises)->wait();

            echo "[i] Test guardian serialization and deserialization" . PHP_EOL;
            foreach ($guardians as $guardian) {
                $serializedGuardian = $guardian->serialize();
                $deserializedGuardian = Guardian::guardianFromJson(json_encode($serializedGuardian));
                self::assertJsonStringEqualsJsonString(
                    json_encode($guardian),
                    json_encode($deserializedGuardian)
                );
            }

            echo "[i] Combine to make public election key" . PHP_EOL;
            $electionKey = $this->mediatorAPI->combineElectionKeys(array_map(function (Guardian $guardian) {
                return $guardian->getElectionKeyPair()->getPublicKey();
            }, $guardians));

            self::assertIsString($electionKey);

            echo "[i] Generate election context" . PHP_EOL;
            $context = $this->mediatorAPI->getElectionContext($this->manifest, $ggi, $electionKey);
            self::assertIsString($context->getCryptoBaseHash());
            self::assertIsString($context->getCryptoExtendedBaseHash());
            self::assertIsString($context->getDescriptionHash());
            self::assertIsString($context->getElgamalPublicKey());
            self::assertIsInt($context->getGuardianCount());
            self::assertIsInt($context->getQuorum());
            $context->validate();

            echo "[i] Generate fake ballots" . PHP_EOL;
            $fakeBallots = TestDataHandler::getFakeBallots($this->manifest, $this->perStyle);
            $contestVotes = [];
            foreach ($fakeBallots as $fakeBallot) {
                foreach ($fakeBallot->getContests() as $contest) {
                    $contestId = $contest->getContest()->generateObjectId();
                    if (!isset($contestVotes[$contestId])) {
                        $contestVotes[$contestId] = [];
                    }
                    foreach ($contest->getSelections() as $selection) {
                        $selectionId = $selection->selection->generateObjectId();
                        if (!isset($contestVotes[$contestId][$selectionId])) {
                            $contestVotes[$contestId][$selectionId] = 0;
                        }
                        if ($selection->vote === "True")
                            $contestVotes[$contestId][$selectionId]++;
                    }
                }
            }
            foreach ($contestVotes as $contestId => $selections) {
                echo $contestId . PHP_EOL;
                foreach ($selections as $selectionId => $expectedVotes) {
                    echo " $selectionId: " . $expectedVotes . PHP_EOL;
                }
            }

            echo "[i] Encrypt ballots" . PHP_EOL;
            $encryptedBallots = [];
            $ballotEncryptionPromises = [];
            for ($i = 0; $i < count($fakeBallots); $i += $i + 1) {
                $ballotEncryptionPromises[] = $this->mediatorAPI->encryptBallotsAsync(
                    $this->manifest, $context, array_slice($fakeBallots, (int)$i, (int)($i + 1))
                )->then(function ($chunkEncryptedBallots) use ($i, $fakeBallots, &$encryptedBallots) {
                    self::assertIsArray($chunkEncryptedBallots);
                    foreach ($chunkEncryptedBallots as $chunkEncryptedBallot)
                        self::assertInstanceOf(stdClass::class, $chunkEncryptedBallot);
                    $encryptedBallots = array_merge($encryptedBallots, $chunkEncryptedBallots);

                    echo "[i] " . count($encryptedBallots) . " ballots encrypted of " . count($fakeBallots) . PHP_EOL;
                });
            }
            Utils::all(array_values($ballotEncryptionPromises))->wait();
            self::assertSameSize($fakeBallots, $encryptedBallots);
            foreach ($encryptedBallots as $encryptedBallot)
                self::assertInstanceOf(stdClass::class, $encryptedBallot);

            echo "[i] Cast and spoil ballots" . PHP_EOL;
            $castedBallots = [];
            $spoiledBallots = [];

            $ballotProcessPromises = [];
            foreach ($encryptedBallots as $i => $encryptedBallot) {
                $willCast = $i % 2 === 0;
                if ($willCast) {
                    $ballotProcessPromises[] = $this->mediatorAPI->castBallotAsync(
                        $this->manifest, $context, $encryptedBallot
                    )->then(
                        function ($castedBallot) use ($encryptedBallot, &$castedBallots) {
                            self::assertEquals($encryptedBallot->object_id, $castedBallot->object_id);
                            self::assertEquals("CAST", $castedBallot->state);
                            self::assertInstanceOf(stdClass::class, $encryptedBallot);
                            $castedBallots[] = $castedBallot;
                        }
                    )->then(
                        function () use (&$fakeBallots, &$spoiledBallots, &$castedBallots) {
                            echo "[i] " . (count($castedBallots) + count($spoiledBallots)) . " cast/spoiled ("
                                . count($castedBallots) . " casted, "
                                . count($spoiledBallots) . " spoiled) of "
                                . count($fakeBallots) . " ballots" . PHP_EOL;
                        }
                    );
                } else {
                    $ballotProcessPromises[] = $this->mediatorAPI->spoilBallotAsync(
                        $this->manifest, $context, $encryptedBallot
                    )->then(
                        function ($spoiledBallot) use ($encryptedBallot, &$spoiledBallots) {
                            self::assertEquals($encryptedBallot->object_id, $spoiledBallot->object_id);
                            self::assertEquals("SPOILED", $spoiledBallot->state);
                            self::assertInstanceOf(stdClass::class, $spoiledBallot);
                            $spoiledBallots[] = $spoiledBallot;
                        }
                    )->then(
                        function () use (&$fakeBallots, &$spoiledBallots, &$castedBallots) {
                            echo "[i] " . (count($castedBallots) + count($spoiledBallots)) . " cast/spoiled ("
                                . count($castedBallots) . " casted, "
                                . count($spoiledBallots) . " spoiled) of "
                                . count($fakeBallots) . " ballots" . PHP_EOL;
                        }
                    );
                }
            }
            Utils::all($ballotProcessPromises)->wait();

            self::assertCount(count($fakeBallots) / 2, $castedBallots);
            self::assertCount(count($fakeBallots) - count($castedBallots), $spoiledBallots);

            echo "[i] Determine tracker words for all ballots" . PHP_EOL;
            $trackerWordsPromises = [];
            foreach (array_merge($castedBallots, $spoiledBallots) as $ballot) {
                $trackerWordsPromises[] = $this->mediatorAPI->getTrackerWordsAsync($ballot->tracking_hash)
                ->then(function ($trackerWords) {
                    self::assertIsString($trackerWords);
                });
            }
            Utils::all($trackerWordsPromises)->wait();

            echo "[i] Start the tally and append to the tally." . PHP_EOL;
            $tally = $this->mediatorAPI->tally($this->manifest, $context, array_slice($castedBallots, 0, 1));
            self::assertInstanceOf(stdClass::class, $tally);
            $tally = $this->mediatorAPI->tally($this->manifest, $context, array_slice($castedBallots, 1), $tally);
            self::assertInstanceOf(stdClass::class, $tally);

            echo "[i] Decrypt the tally shares" . PHP_EOL;
            $decryptedTallyShares = [];
            $tallyShareDecryptPromises = [];
            foreach ($guardians as $guardian) {
                $tallyShareDecryptPromises = $this->guardianAPI->decryptTallyShareAsync(
                    $this->manifest, $context, $guardian, $tally
                )->then(function ($decryptedShare) use ($guardian, &$decryptedTallyShares) {
                    self::assertInstanceOf(stdClass::class, $decryptedShare);
                    $decryptedTallyShares[$guardian->generateObjectId()] = $decryptedShare;

                    echo "[i] " . $guardian->generateObjectId() . " has submitted tally share. " . PHP_EOL;
                });
            }
            Utils::all($tallyShareDecryptPromises)->wait();

            echo "[i] Decrypt the tally" . PHP_EOL;
            $decryptedTally = $this->mediatorAPI->decryptTally(
                $this->manifest,
                $context,
                $tally,
                $decryptedTallyShares
            );
            self::assertInstanceOf(stdClass::class, $decryptedTally);
            self::assertNotNull($decryptedTally->contests);
            echo "[i] Tally decrypted!" . PHP_EOL;
            foreach ($decryptedTally->contests as $contestId => $contest) {
                echo $contestId . PHP_EOL;
                foreach ($contest->selections as $selectionId => $selection) {
                    echo " $selectionId: " . $selection->tally . PHP_EOL;
                }
            }

            echo "[i] Decrypt spoiled ballots" . PHP_EOL;
            $decryptedBallotShares = [];
            $ballotSharePromises = [];
            foreach ($guardians as $guardian) {
                $ballotSharePromises[] = $this->guardianAPI->decryptBallotsAsync(
                    $context, $guardian, $spoiledBallots
                )->then(function ($decryptedBallotShare) use ($guardian, &$decryptedBallotShares) {
                    self::assertInstanceOf(stdClass::class, $decryptedBallotShare);
                    self::assertIsArray($decryptedBallotShare->shares);
                    $decryptedBallotShares[$guardian->generateObjectId()] = $decryptedBallotShare->shares;

                    echo "[i] " . $guardian->generateObjectId() . " has submitted ballot share. " . PHP_EOL;
                });
            }
            Utils::all($ballotSharePromises)->wait();
            $decryptedSpoiledBallots = $this->mediatorAPI->decryptBallots(
                $context, $spoiledBallots, $decryptedBallotShares
            );
            foreach ($spoiledBallots as $spoiledBallot) {
                $ballotId = $spoiledBallot->object_id;
                $spoiledBallot->contests = $decryptedSpoiledBallots->$ballotId;
            }

            echo "[i] Save the election record" . PHP_EOL;
            TestDataHandler::saveElectionRecord(
                get_class($this), $this->manifest, $context, $guardians,
                $castedBallots, $spoiledBallots, $tally, $decryptedTally, $constants
            );
        } catch (UnexpectedResponseException $e) {
            echo "[e] Encountered error!" . PHP_EOL;
            if ($e->response)
                var_dump($e->response->getBody()->getContents());
            if ($e->request)
                var_dump($e->request);
            throw $e;
        } catch (Throwable $e) {
            echo "[e] Encountered error!" . PHP_EOL;
            var_dump($e);
            die(1);
        }
    }

}
