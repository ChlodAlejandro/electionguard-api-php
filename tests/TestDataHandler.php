<?php

require_once __DIR__ . '/../vendor/autoload.php';

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

    /**
     * @throws \ChlodAlejandro\ElectionGuard\Error\InvalidDefinitionException
     */
    public static function getManifest(): Manifest {
        $testContact = (new ContactInformation())
            ->setName("Test contact")
            ->setAddressLines([
                "P. Sherman",
                "42 Wallaby Way",
                "Sydney, NSW, Australia"
            ])
            ->addEmail(new Email("test", "test@example.com"))
            ->addPhone(new Phone("test", "1234567890"));
        $testGeoUnit = (new GeopoliticalUnit())
            ->setName("test-geopolitical-unit")
            ->setType("municipality")
            ->setContactInformation($testContact);
        $testParties = [
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
        $testCandidates = [
            (new Candidate())
                ->setName(new TextContainer([
                    new LocalizedText('en', 'Red Man')
                ]))
                ->setParty($testParties[0]),
            (new Candidate())
                ->setName(new TextContainer([
                    new LocalizedText('en', 'Green Man')
                ]))
                ->setParty($testParties[1]),
            (new Candidate())
                ->setName(new TextContainer([
                    new LocalizedText('en', 'Blue Man')
                ]))
                ->setParty($testParties[2]),
        ];
        $testContests = new ContestCollection([
            (new Contest())
                ->setName("Test Contest")
                ->setElectoralDistrict($testGeoUnit)
                ->setVoteVariation("plurality")
                ->setVotesAllowed(1)
                ->setNumberElected(1)
                ->setBallotTitle(new TextContainer([
                    new LocalizedText('en', 'Test Ballot')
                ]))
                ->setBallotSubtitle(new TextContainer([
                    new LocalizedText('en', 'This is a test ballot.')
                ]))
                ->addBallotSelectionFromCandidate(...$testCandidates)
        ]);
        $testStyles = [
            (new BallotStyle())
                ->setName("Test Style")
                ->addGeopoliticalUnit($testGeoUnit)
        ];

        $manifest = (new Manifest())
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
            ->addGeopoliticalUnit($testGeoUnit)
            ->addParty(...$testParties)
            ->addCandidate(...$testCandidates)
            ->setContests($testContests)
            ->addBallotStyle(...$testStyles);

        $manifest->validate();

        return $manifest;
    }

}
