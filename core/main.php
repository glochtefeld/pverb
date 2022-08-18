<?php

namespace pverb\core {

class PVFailed extends Exception {
    public function __construct($message, $code=0, Throwable $previous=null) {
        parent::__construct($message, $code, $previous);
    }
    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}

class Runner {
    private array $test_callbacks;
    public function add_test($test) {}
    public function nest_test($test) {}

}

class Expectation {
    private $expected;
    private $actual;
    private $result;
    public function expect($state) {
        $this->actual = $state;
    }
    public function actual($state) {
        $this->expected = $state;
    }
    public function compare() {
        return $this->actual === $this->expected ? true : false;
    }
}


function describe( string $state, $action ) { }
function it( string $state, $action) { }

}
  
?>
