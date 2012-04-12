<?php

$errors = array();

/**
 * simple hook to record all errors in a testable way.
 */
function myErrorHandler($type, $msg, $errfile, $errline) {
    global $errors;
    array_push($errors, array('type' => $type, 'msg' => $msg));
}


class TestMultipleInstalledVersions extends PHPUnit_Framework_TestCase {

    public function setUp() {
        global $errors;
        $errors = array();
    }

    /**
     * tests that multiple includes would raise a E_USER_WARNING
     */
    public function test_multiple_includes() {
        global $errors;
        set_error_handler("myErrorHandler");
        include("aweber_api/aweber_api.php");
        restore_error_handler();

        $this->assertEquals(count($errors), 1);
        $this->assertEquals($errors[0]['type'], E_USER_WARNING);
        $this->assertEquals($errors[0]['msg'], 'Duplicate: Another AWeberAPI client library is already in scope.');
    }
}
?>
