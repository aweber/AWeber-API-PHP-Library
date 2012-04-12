<?php
require_once('aweber_api/aweber_api.php');
require_once('mock_adapter.php');

class TestAWeberCollectionParentEntry extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->adapter = get_mock_adapter();
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
        $this->assertEquals($entry->type, 'account');
    }

    public function testCustomFieldsParentShouldBeList() {
        $entry = $this->customFields->getParentEntry();
        $this->assertTrue(is_a($entry, 'AWeberEntry'));
        $this->assertEquals($entry->type, 'list');
    }

    public function testAccountsParentShouldBeNULL() {
        $entry = $this->accounts->getParentEntry();
        $this->assertEquals($entry, NULL);
    }
}
