<?php
require_once('aweber_api/aweber_api.php');
require_once('mock_adapter.php');

class TestAWeberCollectionFind extends PHPUnit_Framework_TestCase {

     public function setUp() {
         $this->adapter = get_mock_adapter();
         $this->subscribers = $this->_getCollection('/accounts/1/lists/303449/subscribers');
         $this->lists = $this->_getCollection('/accounts/1/lists');
         $this->adapter->clearRequests();
     }

     /**
      * Return AWeberCollection
      */
     public function _getCollection($url) {
         $data = $this->adapter->request('GET', $url);
         return new AWeberCollection($data, $url, $this->adapter);
     }

     /**
      * Find That Returns Entries
      */
     public function testFind_ReturnsEntries() {

        $found_subscribers = $this->subscribers->find(array('email' => 'someone@example.com'));

        # Asserts on the API request
        $expected_url = $this->subscribers->url . '?email=someone%40example.com&ws.op=find';
        $this->assertEquals(sizeOf($this->adapter->requestsMade), 2);
        $req = $this->adapter->requestsMade[0];
        $this->assertEquals($req['method'], 'GET');
        $this->assertEquals($req['uri'], $expected_url);

        $req = $this->adapter->requestsMade[1];
        $this->assertEquals($req['method'], 'GET');
        $this->assertEquals($req['uri'], $expected_url . "&ws.show=total_size");

        # Asserts on the returned data
        $this->assertTrue(is_a($found_subscribers, 'AWeberCollection'));
        $this->assertEquals($this->adapter, $found_subscribers->adapter);
        $this->assertEquals($found_subscribers->url, $this->subscribers->url);
        $this->assertEquals($found_subscribers->total_size, 1);
     }

    /**
      * Find That Does Not Return Entries
      */
     public function testFindDoesNot_ReturnsEntries() {

        $found_subscribers = $this->subscribers->find(array('email' => 'nonexist@example.com'));

        # Asserts on the API request
        $expected_url = $this->subscribers->url . '?email=nonexist%40example.com&ws.op=find';
        $this->assertEquals(sizeOf($this->adapter->requestsMade), 2);
        $req = $this->adapter->requestsMade[0];
        $this->assertEquals($req['method'], 'GET');
        $this->assertEquals($req['uri'], $expected_url);

        $req = $this->adapter->requestsMade[1];
        $this->assertEquals($req['method'], 'GET');
        $this->assertEquals($req['uri'], $expected_url . "&ws.show=total_size");

        # Asserts on the returned data
        $this->assertTrue(is_a($found_subscribers, 'AWeberCollection'));
        $this->assertEquals($this->adapter, $found_subscribers->adapter);
        $this->assertEquals($found_subscribers->url, $this->subscribers->url);
        $this->assertEquals($found_subscribers->total_size, 0);
     }

}
