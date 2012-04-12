<?php
require_once('aweber_api/aweber_api.php');
require_once('mock_adapter.php');

class TestAWeberCollection extends PHPUnit_Framework_TestCase {

    /**
     * Run before each test.  Sets up mock adapter, which uses fixture
     * data for requests, and creates a new collection.
     */
    public function setUp() {
        $this->adapter = get_mock_adapter();
        $this->url = '/accounts/1/lists';
        $data = $this->adapter->request('GET', $this->url);
        $this->collection = new AWeberCollection($data, $this->url, $this->adapter);
    }

    /**
     * Should have the total size available.
     */
    public function testShouldHaveTotalSize() {
        $this->assertEquals($this->collection->total_size, 24);
    }

    /**
     * Should have the URL used to generate this collection
     */
    public function testShouldHaveURL() {
        $this->assertEquals($this->collection->url, $this->url);
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
        $this->assertEquals($entry->id, 1701533);
    }

    public function testShouldKnowItsCollectionType() {
        $this->assertEquals($this->collection->type, 'lists');
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

        $this->assertEquals(sizeof($this->collection->data['entries']), 20);

        $entry = $this->collection[19];
        $this->assertEquals($entry->id, 1424745);
        $this->assertTrue(empty($this->adapter->requestsMade));

        $entry = $this->collection[20];
        $this->assertEquals($entry->id, 1364473);
        $this->assertEquals(count($this->adapter->requestsMade), 1);

        $entry = $this->collection[21];
        $this->assertEquals($entry->id, 1211626);
        $this->assertEquals(count($this->adapter->requestsMade), 1);
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
            $this->assertEquals($index, $count);
            $count++;
        }
        $this->assertEquals($count, $this->collection->total_size);
    }

    /**
     * getById - should return an AWeberEntry with the given id
     */
    public function testShouldAllowGetById() {
        $id = 303449;
        $name = 'default303449';
        $this->adapter->clearRequests();
        $entry = $this->collection->getById($id);

        $this->assertEquals($entry->id, $id);
        $this->assertEquals($entry->name, $name);
        $this->assertTrue(is_a($entry, 'AWeberEntry'));
        $this->assertEquals(count($this->adapter->requestsMade), 1);


        $this->assertEquals($this->adapter->requestsMade[0]['uri'],
            '/accounts/1/lists/303449');
    }

    /**
     * Should implement the countable interface, allowing count() and sizeOf()
     * functions to work properly
     */
    public function testShouldAllowCountOperations() {
        $this->assertEquals(count($this->collection), $this->collection->total_size);
        $this->assertEquals(sizeOf($this->collection), $this->collection->total_size);
    }

}
