<?php

class TestAWeberCollectionFind extends UnitTestCase {

     public function setUp() {
         $this->adapter = new MockOAuthAdapter();
         $this->adapter->app = new AWeberServiceProvider();
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
        $this->assertEqual(sizeOf($this->adapter->requestsMade), 2);
        $req = $this->adapter->requestsMade[0];
        $this->assertEqual($req['method'], 'GET');
        $this->assertEqual($req['uri'], $expected_url);

        $req = $this->adapter->requestsMade[1];
        $this->assertEqual($req['method'], 'GET');
        $this->assertEqual($req['uri'], $expected_url . "&ws.show=total_size");

        # Asserts on the returned data
        $this->assertTrue(is_a($found_subscribers, 'AWeberCollection'));
        $this->assertEqual($this->adapter, $found_subscribers->adapter);
        $this->assertEqual($found_subscribers->url, $this->subscribers->url);
        $this->assertEqual($found_subscribers->total_size, 1);
     }

    /**
      * Find That Does Not Return Entries
      */
     public function testFindDoesNot_ReturnsEntries() {

        $found_subscribers = $this->subscribers->find(array('email' => 'nonexist@example.com'));

        # Asserts on the API request
        $expected_url = $this->subscribers->url . '?email=nonexist%40example.com&ws.op=find';
        $this->assertEqual(sizeOf($this->adapter->requestsMade), 2);
        $req = $this->adapter->requestsMade[0];
        $this->assertEqual($req['method'], 'GET');
        $this->assertEqual($req['uri'], $expected_url);

        $req = $this->adapter->requestsMade[1];
        $this->assertEqual($req['method'], 'GET');
        $this->assertEqual($req['uri'], $expected_url . "&ws.show=total_size");

        # Asserts on the returned data
        $this->assertTrue(is_a($found_subscribers, 'AWeberCollection'));
        $this->assertEqual($this->adapter, $found_subscribers->adapter);
        $this->assertEqual($found_subscribers->url, $this->subscribers->url);
        $this->assertEqual($found_subscribers->total_size, 0);
     }

}

class TestAWeberCreateEntry extends UnitTestCase {

    public function setUp() {
        $this->adapter = new MockOAuthAdapter();
        $this->adapter->app = new AWeberServiceProvider();

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


         $this->assertEqual(sizeOf($this->adapter->requestsMade), 2);

         $req = $this->adapter->requestsMade[0];
         $this->assertEqual($req['method'], 'POST');
         $this->assertEqual($req['uri'], $this->custom_fields->url);
         $this->assertEqual($req['data'], array(
             'ws.op' => 'create',
             'name' => 'AwesomeField'));

         $req = $this->adapter->requestsMade[1];
         $this->assertEqual($req['method'], 'GET');
         $this->assertEqual($req['uri'], '/accounts/1/lists/303449/custom_fields/2');
     }
}

class TestAWeberCollection extends UnitTestCase {

    /**
     * Run before each test.  Sets up mock adapter, which uses fixture
     * data for requests, and creates a new collection.
     */
    public function setUp() {
        $this->adapter = new MockOAuthAdapter();
        $this->collection = new AWeberCollection(MockData::load('lists/page3'), '/accounts/1/lists', $this->adapter);
    }

    /**
     * Should have the total size available.
     */
    public function testShouldHaveTotalSize() {
        $this->assertEqual($this->collection->total_size, 24);
    }

    /**
     * Should have the URL used to generate this collection 
     */
    public function testShouldHaveURL() {
        $this->assertEqual($this->collection->url, '/accounts/1/lists');
    }

    /**
     * Should not allow direct access to the entries data retreived from
     * the request.
     */
    public function testShouldNotAccessEntries() {
        $this->assertNull($this->collection->entries);
    }

    /**
     * Should allow entries to be accessed as an array
     */
    public function testShouldAccessEntiresAsArray() {
        $entry = $this->collection[0];
        $this->assertTrue(is_a($entry, 'AWeberResponse'));
        $this->assertTrue(is_a($entry, 'AWeberEntry'));
        $this->assertEqual($entry->id, 251847);
    }

    public function testShouldKnowItsCollectionType() {
        $this->assertEqual($this->collection->type, 'lists');
    }

    /**
     * When accessing an offset out of range, should return null
     */
    public function testShouldNotAccessEntriesOutOfRange() {
        $this->assertNull($this->collection[26]);
    }

    /**
     * When accessing entries by offset, should only make a request when
     * accessing entries not in currenlty loaded pages.
     */
    public function testShouldLazilyLoadAdditionalPages() {
        $this->adapter->clearRequests();

        $entry = $this->collection[19];
        $this->assertEqual($entry->id, 50000003);
        $this->assertTrue(empty($this->adapter->requestsMade));

        $entry = $this->collection[20];
        $this->assertEqual($entry->id, 1364473);
        $this->assertEqual(count($this->adapter->requestsMade), 1);

        $entry = $this->collection[21];
        $this->assertEqual($entry->id, 1211626);
        $this->assertEqual(count($this->adapter->requestsMade), 1);
    }

    /**
     * Should implement the Iterator interface
     */
    public function testShouldBeAnIterator() {
        $this->assertTrue(is_a($this->collection, 'Iterator'));
    }

    /**
     * When accessed as an iterator, should return entries by offset,
     * from 0 to n-1.
     */
    public function testShouldAllowIteration() {
        $count = 0;
        foreach ($this->collection as $index => $entry) {
            $this->assertEqual($index, $count);
            $count++;
        }
        $this->assertEqual($count, $this->collection->total_size);
    }

    /**
     * getById - should return an AWeberEntry with the given id
     */
    public function testShouldAllowGetById() {
        $id = 303449;
        $name = 'default303449';
        $entry = $this->collection->getById($id);

        $this->assertEqual($entry->id, $id);
        $this->assertEqual($entry->name, $name);
        $this->assertTrue(is_a($entry, 'AWeberEntry'));
        $this->assertEqual(count($this->adapter->requestsMade), 1);

        $this->assertEqual($this->adapter->requestsMade[0]['uri'],
            '/accounts/1/lists/303449');
    }

    /**
     * Should implement the countable interface, allowing count() and sizeOf() 
     * functions to work properly
     */
    public function testShouldAllowCountOperations() {
        $this->assertEqual(count($this->collection), $this->collection->total_size);
        $this->assertEqual(sizeOf($this->collection), $this->collection->total_size);
    }

}

class TestGettingCollectionParentEntry extends UnitTestCase {

    public function setUp() {
        $this->adapter = new MockOAuthAdapter();
        $this->adapter->app = new AWeberServiceProvider();
        $url = '/accounts/1/lists';
        $data = $this->adapter->request('GET', $url);
        $this->lists = new AWeberCollection($data, $url, $this->adapter);
        $url = '/accounts';
        $data = $this->adapter->request('GET', $url);
        $this->accounts = new AWeberCollection($data, $url, $this->adapter);
        $url = '/accounts/1/lists/303449/custom_fields';
        $data = $this->adapter->request('GET', $url);
        $this->customFields = new AWeberCollection($data, $url, $this->adapter);
    }

    public function testListsParentShouldBeAccount() {
        $entry = $this->lists->getParentEntry();
        $this->assertTrue(is_a($entry, 'AWeberEntry'));
        $this->assertEqual($entry->type, 'account');
    }

    public function testCustomFieldsParentShouldBeList() {
        $entry = $this->customFields->getParentEntry();
        $this->assertTrue(is_a($entry, 'AWeberEntry'));
        $this->assertEqual($entry->type, 'list');
    }

    public function testAccountsParentShouldBeNULL() {
        $entry = $this->accounts->getParentEntry();
        $this->assertEqual($entry, NULL);
    }
}
