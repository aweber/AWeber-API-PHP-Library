<?php
require_once('aweber_api/aweber_api.php');
require_once('mock_adapter.php');

class TestAWeberCreateEntry extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->adapter = get_mock_adapter();

        # Get CustomFields
        $url = '/accounts/1/lists/303449/custom_fields';
        $data = $this->adapter->request('GET', $url);
        $this->custom_fields = new AWeberCollection($data, $url, $this->adapter);

    }

    /**
     * Create Succeeded
     */
    public function testCreate_Success() {

         $this->adapter->clearRequests();
         $resp = $this->custom_fields->create(array('name' => 'AwesomeField'));


         $this->assertEquals(sizeOf($this->adapter->requestsMade), 2);

         $req = $this->adapter->requestsMade[0];
         $this->assertEquals($req['method'], 'POST');
         $this->assertEquals($req['uri'], $this->custom_fields->url);
         $this->assertEquals($req['data'], array(
             'ws.op' => 'create',
             'name' => 'AwesomeField'));

         $req = $this->adapter->requestsMade[1];
         $this->assertEquals($req['method'], 'GET');
         $this->assertEquals($req['uri'], '/accounts/1/lists/303449/custom_fields/2');
     }
}
