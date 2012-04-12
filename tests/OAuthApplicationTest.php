<?php
require_once('aweber_api/aweber_api.php');

if (!class_exists('Object')) {
    class Object {}
}

class TestOAuthAppliation extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $parentApp = false;
        $this->oauth = new OAuthApplication($parentApp);
        $this->oauth->consumerSecret = 'CONSUMERSECRET';
        $this->oauth->consumerKey = 'consumer_key';
    }

    /**
     * testUniqueNonce
     *
     * GenerateNonce should generate a unique string
     * @access public
     * @return void
     */
    public function testUniqueNonce() {
        $values = array();
        foreach (range(1,100) as $i) {
            $val = $this->oauth->generateNonce();
            $this->assertFalse(in_array($val, $values), 'Generated nonce should be unique');
            $values[] = $val;
        }
    }

    public function testAddGetParams() {
        $url = 'http://www.sometestsite.com/';
        $data = array(
            'keyA' => 'Some Value',
            'keyC' => 'some other value',
            'keyB' => 'yet another value',
        );

        $this->assertEquals(
            'keyA=Some%20Value&keyB=yet%20another%20value&keyC=some%20other%20value',
            $this->oauth->buildData($data));
    }

    /**
     * testUniqueNonceSameTime
     *
     * GenerateNonce should generate unique strings, even with the same timestamp
     * @access public
     * @return void
     */
    public function testUniqueNonceSameTime() {
        $time = time();
        $values = array();
        foreach (range(1,100) as $i) {
            $val = $this->oauth->generateNonce($time);
            $this->assertFalse(in_array($val, $values), 'Generated nonce should be unique,'.
                        ' even with identical timestamp');
            $values[] = $val;
        }
    }

    /**
     * generateTimestamp
     *
     * Ensure generateTimestamp returns a time in epoch seconds.
     * @access public
     * @return void
     */
    public function testGenerateTimestamp() {
        $time = $this->oauth->generateTimestamp();
        $this->assertTrue(is_int($time), 'Timestamp must be an integer');
        $this->assertTrue($time > 0, 'Timestamp must be positive.');
        $this->assertTrue($this->oauth->generateTimestamp() >= $time,
            'Multiple calls to generateTimestamp should always be equal or greater.');
    }

    /**
     * testCreateSignature
     *
     * Test that a new signature is generated based on the data
     * @access public
     * @return void
     */
    public function testCreateSignature() {
        $sigBase = '342refd435gdfxw354xfbw364fdg'; // Random string
        $sigKey  = 'gdgdfet4gdffgd4etgr'; // Random string as well
        $signature = $this->oauth->createSignature($sigBase, $sigKey);
        $this->assertNotEmpty($signature, 'Returns a valid signature');
        $this->assertTrue(strpos($signature, $sigBase) === false, 'Signature does not contain base');
        $this->assertTrue(strpos($signature, $sigKey) === false, 'Signature does not contain key');
    }

    /**
     * testCreateSignatureUniqueness
     *
     * Verify that signatures are unique
     * @access public
     * @return void
     */
    public function testCreateSignatureUniqueness() {
        $sigBase = '342refd435gdfxw354xfbw364fdg'; // Random string
        $sigKey  = 'gdgdfet4gdffgd4etgr'; // Random string as well
        $signature  = $this->oauth->createSignature($sigBase, $sigKey);
        $signature2 = $this->oauth->createSignature($sigBase, $sigKey);
        $this->assertEquals($signature, $signature2, 'Signatures with same parameters are identical.');
        $sigKey = $sigKey.'1';

        $sig3 = $this->oauth->createSignature($sigBase, $sigKey);
        $this->assertNotEquals($signature, $sig3, 'Changing key creates different signature');
    }


    /**
     * testGetVersion
     *
     * Tests that the default OAuth version is currently 1.0
     * @access public
     * @return void
     */
    public function testGetVersion() {
        $version = $this->oauth->version;
        $this->assertEquals($version, '1.0', 'Default version is 1.0');
    }

    /**
     * testOAuthUser
     *
     * Tests the the OAuthUser class exists and has all its necessary data
     * @access public
     * @return void
     */
    public function testOAuthUser() {
        $user = new OAuthUser();

        $this->assertFalse($user->requestToken);
        $this->assertFalse($user->tokenSecret);
        $this->assertFalse($user->authorizedToken);
        $this->assertFalse($user->accessToken);
    }

    /**
     * generateOAuthUser
     *
     * Generate a mock OAuth user
     *
     * @access protected
     * @return void
     */
    protected function generateOAuthUser() {
        $data = array(
            'token'    => 'authorized token',
            'secret'   => 'abcdefg',
        );

        $user = new OAuthUser();
        $user->accessToken = $data['token'];
        $user->tokenSecret = $data['secret'];

        return array($user, $data);
    }

    /**
     * testCreatSignatureKey
     *
     * Test that signature key is generated correctly
     * @access public
     * @return void
     */
    public function testCreatSignatureKey() {
        list($user, $data) = $this->generateOAuthUser();
        $this->oauth->user = $user;

        $sigKey = $this->oauth->createSignatureKey();
        $this->assertEquals('CONSUMERSECRET&abcdefg', $sigKey); //, 'Signature key generated matches');
    }

    /**
     * testGetOAuthRequestData
     *
     * @access public
     * @return void
     */
    public function testGetOAuthRequestData() {
        $this->oauth->user = new OAuthUser();
        $data = $this->oauth->getOAuthRequestData();
        $tempData =  array(
            'oauth_consumer_key' => 'consumer_key',
            'oauth_token' =>  '',
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_version' => '1.0');

        // Check that timestamp and nonce are set.
        $this->assertTrue(!empty($data['oauth_timestamp']));
        $this->assertTrue(!empty($data['oauth_nonce']));

        // Remove those two items, since they are always unique
        unset($data['oauth_timestamp']);
        unset($data['oauth_nonce']);

        ksort($data);
        ksort($tempData);

        $this->assertSame($data, $tempData, 'Aside from timestamp and nonce, the rest should be identical');
    }

    public function generateRequestData() {
        list($user, $data) = $this->generateOAuthUser();
        $this->oauth->user = $user;

        $requestData = array(
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3');

        $mergeData = $this->oauth->mergeOAuthData($requestData);
        return array($mergeData, $requestData);
    }

    public function testMergeOAuthData() {
        list($mergeData, $requestData) = $this->generateRequestData();
        $this->assertEquals($mergeData['key1'], $requestData['key1']);
        $this->assertEquals($mergeData['oauth_consumer_key'], $this->oauth->consumerKey);
    }

    public function testCreateSignatureBase() {
        list($mergeData, $requestData) = $this->generateRequestData();
        $method = 'GET';
        $url = 'http://www.someservice.com/chicken-nuggets';

        $baseString = $this->oauth->createSignatureBase($method, $url, $mergeData);
        $this->assertNotEmpty($baseString);
        $this->assertTrue(strpos($baseString, $method) !== false);
        $this->assertTrue(strpos($baseString, urlencode($url))!== false);
    }

    public function testSignRequest() {
        list($data, $requestData) = $this->generateRequestData();
        $method = 'GET';
        $url = 'http://www.someservice.com/chicken-nuggets';

        $signedData = $this->oauth->signRequest($method, $url, $data);
        foreach ($data as $key => $val) {
            $this->assertEquals($signedData[$key], $val, 'Signed data has correct value for "'.$key.'"');
        }

        $this->assertTrue(!empty($signedData['oauth_signature']));
    }

    /**
     * testPrepareRequest
     *
     * @access public
     * @return void
     */
    public function testPrepareRequest() {
        list($data, $requestData) = $this->generateRequestData();
        $method = 'GET';
        $url = 'http://www.someservice.com/chicken-nuggets';

        $signedData = $this->oauth->prepareRequest($method, $url, $requestData);

        // Test that a nonce and timestamp was generated, then remove the one from our base data so that we
        // don't try to compare the two. They should still be different.
        $this->assertTrue(!empty($signedData['oauth_nonce']), 'Verify nonce was generated');
        $this->assertTrue(!empty($signedData['oauth_timestamp']), 'Verify nonce was generated');
        unset($data['oauth_nonce']);
        unset($data['oauth_timestamp']);

        foreach ($data as $key => $val) {
            $this->assertEquals($signedData[$key], $val, 'Signed data has correct value for "'.$key.'"');
        }

        $this->assertTrue(!empty($signedData['oauth_signature']));
    }

    /**
     * testParseResponse
     *
     * @access public
     * @return void
     */
    public function testParseResponse() {
        $response = new Object();
        $response->body = 'oauth_token=oTkBjHdPYyP7j13RffGpllNhktOR775h6jk48D1cu8Y&oauth_token_secret=GRRa1E7MMm526nql1hETKHMu2BvAXpvHaCu332TPAJ4&oauth_callback_confirmed=true';
        $data = $this->oauth->parseResponse($response);
        $dataShouldBe = array(
            'oauth_token' => 'oTkBjHdPYyP7j13RffGpllNhktOR775h6jk48D1cu8Y',
            'oauth_token_secret' => 'GRRa1E7MMm526nql1hETKHMu2BvAXpvHaCu332TPAJ4',
            'oauth_callback_confirmed' => 'true',
        );
        $this->assertSame($data, $dataShouldBe, 'Data is parsed correctly.');
    }

}
