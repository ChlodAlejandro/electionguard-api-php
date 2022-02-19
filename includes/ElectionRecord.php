<?php

namespace ChlodAlejandro\ElectionGuard;

use ChlodAlejandro\ElectionGuard\Schema\ElectionContext;
use ChlodAlejandro\ElectionGuard\Schema\Manifest\Manifest;
use stdClass;

class ElectionRecord {

    /**
     * @param string $outputFolder
     * @param \ChlodAlejandro\ElectionGuard\Schema\Manifest\Manifest $manifest
     * @param \ChlodAlejandro\ElectionGuard\Schema\ElectionContext $context
     * @param \ChlodAlejandro\ElectionGuard\Schema\Guardian\Guardian[] $guardians
     * @param \stdClass[] $castedBallots
     * @param \stdClass[] $decryptedSpoiledBallots
     * @param \stdClass $encryptedTally
     * @param \stdClass $tally
     * @param \stdClass $constants
     * @param \stdClass|null $coefficients
     * @return void
     */
    public static function save(
        string          $outputFolder,
        Manifest        $manifest,
        ElectionContext $context,
        array           $guardians,
        array           $castedBallots,
        array           $decryptedSpoiledBallots,
        stdClass        $encryptedTally,
        stdClass        $tally,
        stdClass        $constants,
        ?stdClass       $coefficients = null
    ) {
        if (is_dir($outputFolder) === false) {
            mkdir($outputFolder);
        } else {
            function deleteTree($dir, $deleteRoot = true) {
                $files = array_diff(scandir($dir), array(".", ".."));
                foreach ($files as $file) {
                    (is_dir("$dir/$file")) ? deleteTree("$dir/$file") : unlink("$dir/$file");
                }
                if ($deleteRoot) {
                    rmdir($dir);
                }
            }
            deleteTree($outputFolder, false);
        }

        file_put_contents(
            $outputFolder . DIRECTORY_SEPARATOR . "manifest.json",
            json_encode($manifest->serialize(), JSON_PRETTY_PRINT)
        );
        file_put_contents(
            $outputFolder . DIRECTORY_SEPARATOR . "context.json",
            json_encode($context->serialize(), JSON_PRETTY_PRINT)
        );
        file_put_contents(
            $outputFolder . DIRECTORY_SEPARATOR . "tally.json",
            json_encode($tally, JSON_PRETTY_PRINT)
        );
        file_put_contents(
            $outputFolder . DIRECTORY_SEPARATOR . "encrypted_tally.json",
            json_encode($encryptedTally, JSON_PRETTY_PRINT)
        );
        file_put_contents(
            $outputFolder . DIRECTORY_SEPARATOR . "constants.json",
            json_encode($constants, JSON_PRETTY_PRINT)
        );
        if (isset($coefficients)) {
            file_put_contents(
                $outputFolder . DIRECTORY_SEPARATOR . "coefficients.json",
                json_encode($coefficients, JSON_PRETTY_PRINT)
            );
        }

        $guardianDirectory = $outputFolder . DIRECTORY_SEPARATOR . "guardians";
        mkdir($guardianDirectory);
        foreach ($guardians as $guardian) {
            file_put_contents(
                $guardianDirectory . DIRECTORY_SEPARATOR . $guardian->generateObjectId() . ".json",
                json_encode($guardian->serialize(), JSON_PRETTY_PRINT)
            );
        }
        $submittedBallotsDirectory = $outputFolder . DIRECTORY_SEPARATOR . "submitted_ballots";
        mkdir($submittedBallotsDirectory);
        foreach ($castedBallots as $castedBallot) {
            file_put_contents(
                $submittedBallotsDirectory . DIRECTORY_SEPARATOR . $castedBallot->object_id . ".json",
                json_encode($castedBallot, JSON_PRETTY_PRINT)
            );
        }
        $spoiledBallotsDirectory = $outputFolder . DIRECTORY_SEPARATOR . "spoiled_ballots";
        mkdir($spoiledBallotsDirectory);
        foreach ($decryptedSpoiledBallots as $spoiledBallot) {
            file_put_contents(
                $spoiledBallotsDirectory . DIRECTORY_SEPARATOR . $spoiledBallot->object_id . ".json",
                json_encode($spoiledBallot, JSON_PRETTY_PRINT)
            );
        }
    }

}
