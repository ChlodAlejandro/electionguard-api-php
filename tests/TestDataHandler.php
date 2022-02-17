<?php

require_once __DIR__ . '/../vendor/autoload.php';

use ChlodAlejandro\ElectionGuard\Schema\Ballot\Ballot;
use ChlodAlejandro\ElectionGuard\Schema\Ballot\BallotContest;
use ChlodAlejandro\ElectionGuard\Schema\Ballot\BallotSelection;
use ChlodAlejandro\ElectionGuard\Schema\Manifest\BallotStyle;
use ChlodAlejandro\ElectionGuard\Schema\Manifest\Candidate;
use ChlodAlejandro\ElectionGuard\Schema\Manifest\ContactInformation;
use ChlodAlejandro\ElectionGuard\Schema\Manifest\Contest;
use ChlodAlejandro\ElectionGuard\Schema\Manifest\ContestCollection;
use ChlodAlejandro\ElectionGuard\Schema\Manifest\Email;
use ChlodAlejandro\ElectionGuard\Schema\Manifest\GeopoliticalUnit;
use ChlodAlejandro\ElectionGuard\Schema\Manifest\LocalizedText;
use ChlodAlejandro\ElectionGuard\Schema\Manifest\Manifest;
use ChlodAlejandro\ElectionGuard\Schema\Manifest\Party;
use ChlodAlejandro\ElectionGuard\Schema\Manifest\Phone;
use ChlodAlejandro\ElectionGuard\Schema\Manifest\TextContainer;

class TestDataHandler {

    /** @var ContactInformation */
    static $testContact;
    /** @var GeopoliticalUnit */
    static $testGeoUnit;
    /** @var Party[] */
    static $testParties;
    /** @var \ChlodAlejandro\ElectionGuard\Schema\Manifest\Candidate[] */
    static $testCandidates;
    /** @var ContestCollection */
    static $testContests;
    /** @var \ChlodAlejandro\ElectionGuard\Schema\Manifest\BallotStyle[] */
    static $testStyles;
    /** @var Manifest */
    static $manifest;

    /**
     * @throws \ChlodAlejandro\ElectionGuard\Error\InvalidDefinitionException
     */
    public static function getManifest(): Manifest {
        if (empty(self::$testContact))
            self::$testContact = (new ContactInformation())
                ->setName("Test contact")
                ->setAddressLines([
                    "P. Sherman",
                    "42 Wallaby Way",
                    "Sydney, NSW, Australia"
                ])
                ->addEmail(new Email("test", "test@example.com"))
                ->addPhone(new Phone("test", "1234567890"));
        if (empty(self::$testGeoUnit))
            self::$testGeoUnit = (new GeopoliticalUnit())
                ->setName("test-geopolitical-unit")
                ->setType("municipality")
                ->setContactInformation(self::$testContact);
        if (empty(self::$testParties))
            self::$testParties = [
                (new Party())
                    ->setName(new TextContainer([
                        new LocalizedText('en', 'Red Party')
                    ]))
                    ->setColor("FF0000")
                    ->setAbbreviation("RP")
                    ->setLogoUri("file:///red_party.png"),
                (new Party())
                    ->setName(new TextContainer([
                        new LocalizedText('en', 'Green Party')
                    ]))
                    ->setColor("00FF00")
                    ->setAbbreviation("GP")
                    ->setLogoUri("file:///green_party.png"),
                (new Party())
                    ->setName(new TextContainer([
                        new LocalizedText('en', 'Blue Party')
                    ]))
                    ->setColor("0000FF")
                    ->setAbbreviation("BP")
                    ->setLogoUri("file:///blue_party.png")
            ];
        if (empty(self::$testCandidates))
            self::$testCandidates = [
                (new Candidate())
                    ->setName(new TextContainer([
                        new LocalizedText('en', 'Red Man')
                    ]))
                    ->setParty(self::$testParties[0]),
                (new Candidate())
                    ->setName(new TextContainer([
                        new LocalizedText('en', 'Green Man')
                    ]))
                    ->setParty(self::$testParties[1]),
                (new Candidate())
                    ->setName(new TextContainer([
                        new LocalizedText('en', 'Blue Man')
                    ]))
                    ->setParty(self::$testParties[2]),
            ];
        if (empty(self::$testContests))
            self::$testContests = new ContestCollection([
                (new Contest())
                    ->setName("Test Contest")
                    ->setElectoralDistrict(self::$testGeoUnit)
                    ->setVoteVariation("plurality")
                    ->setVotesAllowed(1)
                    ->setNumberElected(1)
                    ->setBallotTitle(new TextContainer([
                        new LocalizedText('en', 'Test Ballot')
                    ]))
                    ->setBallotSubtitle(new TextContainer([
                        new LocalizedText('en', 'This is a test ballot.')
                    ]))
                    ->addBallotSelectionFromCandidate(...self::$testCandidates)
            ]);
        if (empty(self::$testStyles))
            self::$testStyles = [
                (new BallotStyle())
                    ->setName("Test Style")
                    ->addGeopoliticalUnit(self::$testGeoUnit)
            ];

        if (empty(self::$manifest))
            self::$manifest = (new Manifest())
                ->setName(new TextContainer([
                    new LocalizedText('en', 'Test Election')
                ]))
                ->setElectionScopeId("test-election-scope-id")
                ->setStartDate(new DateTime("yesterday"))
                ->setEndDate(new DateTime("tomorrow"))
                ->setElectionScopeId("test-election")
                ->setType("primary")
                ->setContactInformation((new ContactInformation())
                    ->setAddressLines([
                        "P. Sherman",
                        "42 Wallaby Way",
                        "Sydney, NSW, Australia"
                    ])
                    ->addEmail(new Email("test", "test@example.com"))
                    ->addPhone(new Phone("test", "1234567890"))
                )
                ->addGeopoliticalUnit(self::$testGeoUnit)
                ->addParty(...self::$testParties)
                ->addCandidate(...self::$testCandidates)
                ->setContests(self::$testContests)
                ->addBallotStyle(...self::$testStyles);

        self::$manifest->validate();

        return self::$manifest;
    }

    /**
     * @param \ChlodAlejandro\ElectionGuard\Schema\Manifest\Manifest $manifest
     * @param \ChlodAlejandro\ElectionGuard\Schema\Manifest\BallotStyle $ballotStyle
     * @return \ChlodAlejandro\ElectionGuard\Schema\Ballot\Ballot
     */
    public static function getFakeBallot(Manifest $manifest, BallotStyle $ballotStyle): Ballot {
        $ballot = new Ballot(
            "ballot-" . sha1($ballotStyle->generateObjectId()),
            $manifest,
            $ballotStyle
        );

        foreach ($manifest->getBallotStyleContests($ballotStyle) as $contest) {
            $selections = $contest->getBallotSelections();
            $ballotSelections = [
                new BallotSelection(
                    $selections[rand(0, count($selections) - 1)],
                    "True"
                )
            ];

            $ballot->addContest(new BallotContest(
                $contest,
                $ballotSelections
            ));
        }

        return $ballot;
    }

    /**
     * @param \ChlodAlejandro\ElectionGuard\Schema\Manifest\Manifest $manifest
     * @param int $perStyle
     * @return \ChlodAlejandro\ElectionGuard\Schema\Ballot\Ballot[]
     */
    public static function getFakeBallots(Manifest $manifest, int $perStyle = 1): array {
        $ballots = [];
        foreach ($manifest->getBallotStyles() as $ballotStyle) {
            for ($i = 0; $i < $perStyle; $i++) {
                $ballots[] = self::getFakeBallot($manifest, $ballotStyle);
            }
        }
        return $ballots;
    }

}
