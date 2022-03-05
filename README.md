# ElectionGuard API for PHP
Provides a stateful, object-oriented interface to the ElectionGuard API in PHP.

## Usage

This library exposes two main classes: `Mediator` and `Guardian`, each representing a Mediator-mode
and Guardian-mode ElectionGuard server. In each class' constructor, you may pass a single API server URL
or an array (suggested). Either situation will work as the API is expected to be completely stateless.

For an implementation of this, see the [EndToEndElectionTest.php](/tests/EndToEndElectionTest.php) file.

## TODO
- Add fallback to other API endpoints for cases where an API endpoint returns a network error. 
