<?php


class TestAWeberEntry extends UnitTestCase {

    /**
     * Before each test, sets up mock adapter to fake requests with fixture
     * data and AWeberEntry based on list 303449
     */
    public function setUp() {
        $this->adapter = new MockOAuthAdapter();

        $url = '/accounts/1/lists/303449';
        $data = $this->adapter->request('GET', $url);
        $this->entry = new AWeberEntry($data, $url, $this->adapter);
    }

    /**
     * Should be an AWeberEntry
     */
    public function testShouldBeAnAWeberEntry() {
        $this->assertTrue(is_a($this->entry, 'AWeberEntry'));
    }

    /**
     * AWeberEntry should be an AWeberResponse
     */
    public function testShouldBeAnAWeberResponse() {
        $this->assertTrue(is_a($this->entry, 'AWeberResponse'));
    }

    /**
     * Should be able to access the id property (global to all entries)
     */
    public function testShouldBeAbleToAccessId() {
        $this->assertEqual($this->entry->id, 303449);
    }

    /**
     * Should be able to access name (or any property unique to the response)
     */
    public function testShouldBeAbleToAccessName() {
        $this->assertEqual($this->entry->name, 'default303449');
    }

    /**
     * Should be able to discern its type based on its data
     */
    public function testShouldKnowItsType() {
        $this->assertEqual($this->entry->type, 'list');
    }

    /**
     * When access properties it does not have, but are known sub collections,
     * it will request for it and return the new collection object. 
     */
    public function testShouldProvidedCollections() {
        $this->adapter->clearRequests();
        $campaigns = $this->entry->campaigns;

        $this->assertTrue(is_a($campaigns, 'AWeberCollection'));
        $this->assertEqual(count($this->adapter->requestsMade), 1);
        $this->assertEqual($this->adapter->requestsMade[0]['uri'],
            '/accounts/1/lists/303449/campaigns');
    }

    /**
     * When accessing non-implemented children of a resource, should raised
     * a not implemented exception
     */
    public function testShouldThrowExceptionIfNotImplemented() {
        $this->adapter->clearRequests();
        try {
            $obj = $this->entry->something_not_implemented;
            $this->assertFalse(true, "This should not get called due to exception raising.");
        }
        catch (Exception $e) {
            $this->assertTrue(is_a($e, 'AWeberException'));
            $this->assertTrue(is_a($e, 'AWeberResourceNotImplemented'));
        }
        $this->assertEqual(count($this->adapter->requestsMade), 0);
    }

    /**
     * Should return the name of all attributes and collections in this entry
     */
    public function testAttrs() {
        $this->assertEqual($this->entry->attrs(),
            array(
                'id'                   => 303449,
                'name'                 => 'default303449',
                'self_link'            => 'https://api.aweber.com/1.0/accounts/1/lists/303449',
                'campaigns'            => 'collection',
                'subscribers'          => 'collection',
                'web_forms'            => 'collection',
                'custom_fields'        => 'collection',
                'web_form_split_tests' => 'collection',
            )
        );
    }

    /**
     * Should be able to delete an entry, and it will send a DELETE request to the
     * API servers to its URL
     */
    public function testDelete() {
        $this->adapter->clearRequests();
        $resp = $this->entry->delete();
        $this->assertIdentical($resp, true);
        $this->assertEqual(sizeOf($this->adapter->requestsMade), 1);
        $this->assertEqual($this->adapter->requestsMade[0]['method'], 'DELETE');
        $this->assertEqual($this->adapter->requestsMade[0]['uri'], $this->entry->url);
    }

    /**
     * When delete returns a non-200 status code, the delete failed and false is
     * returned.
     */
    public function testFailedDelete() {
        $url = '/accounts/1';
        $data = $this->adapter->request('GET', $url);
        $entry = new AWeberEntry($data, $url, $this->adapter);

        // Can't delete account
        $resp = $entry->delete();
        $this->assertIdentical($resp, false);
    }

    /**
     *  Should be able to change a property in an entry's data array directly on
     *  the object, and have that change propogate to its data array
     *  
     */
    public function testSet() {
        $this->assertNotEqual($this->entry->name, 'mynewlistname');
        $this->assertNotEqual($this->entry->data['name'], 'mynewlistname');
        $this->entry->name = 'mynewlistname';
        $this->assertEqual($this->entry->name, 'mynewlistname');
        $this->assertEqual($this->entry->data['name'], 'mynewlistname');
    }

    /**
     * Should Color a request when a save is made.
     */
    public function testSave() {
        $this->entry->name = 'mynewlistname';
        $this->adapter->clearRequests();
        $resp = $this->entry->save();
        $this->assertEqual(sizeOf($this->adapter->requestsMade), 1);
        $req = $this->adapter->requestsMade[0];
        $this->assertEqual($req['method'], 'PATCH');
        $this->assertEqual($req['uri'], $this->entry->url);
        $this->assertEqual($req['data'], array('name' => 'mynewlistname'));
        $this->assertIdentical($resp, true);
    }

    public function testSaveFailed() {
        $url = '/accounts/1/lists/505454';
        $data = $this->adapter->request('GET', $url);
        $entry = new AWeberEntry($data, $url, $this->adapter);
        $entry->name = 'foobarbaz';
        $resp = $entry->save();
        $this->assertIdentical($resp, false);
    }

    /**
     * Should keep track of whether or not this entry is "dirty".  It should
     * not Color save calls if it hasn't been altered since the last successful
     * load / save operation.
     */
    public function testShouldMaintainDirtiness() {
        $this->adapter->clearRequests();
        $resp = $this->entry->save();
        $this->assertEqual(sizeOf($this->adapter->requestsMade), 0);
        $this->entry->name = 'mynewlistname';
        $resp = $this->entry->save();
        $this->assertEqual(sizeOf($this->adapter->requestsMade), 1);
        $resp = $this->entry->save();
        $this->assertEqual(sizeOf($this->adapter->requestsMade), 1);
    }


}

class AccountTestCase extends UnitTestCase {

    public function setUp() {
        $this->adapter = new MockOAuthAdapter();
        $this->adapter->app = new AWeberServiceProvider();
        $url = '/accounts/1';
        $data = $this->adapter->request('GET', $url);
        $this->entry = new AWeberEntry($data, $url, $this->adapter);
    }
}

/**
 * TestAWeberAccountEntry
 *
 * Account entries have a handful of special named operations. This asserts
 * that they behave as expected.
 *
 * @uses UnitTestCase
 * @package 
 * @version $id$
 */
class TestAWeberAccountEntry extends AccountTestCase {

    public function testIsAccount() {
        $this->assertEqual($this->entry->type, 'account');
    }
}

class TestAccountGetWebForms extends AccountTestCase {

    public function setUp() {
        parent::setUp();
        $this->forms = $this->entry->getWebForms();
    }

    public function testShouldReturnArray() {
        $this->assertTrue(is_array($this->forms));
    }

    public function testShouldHaveCorrectCountOfEntries() {
        $this->assertEqual(sizeOf($this->forms), 181);
    }

    public function testShouldHaveEntries() {
        foreach($this->forms as $entry) {
            $this->assertTrue(is_a($entry, 'AWeberEntry'));
        }
    }

    public function testShouldHaveFullURL() {
        foreach($this->forms as $entry) {
            $this->assertTrue(preg_match('/^\/accounts\/1\/lists\/[0-9]*\/web_forms\/[0-9]*$/', $entry->url));
        }
    }
}

class TestAccountGetWebFormSplitTests extends AccountTestCase {

    public function setUp() {
        parent::setUp();
        $this->forms = $this->entry->getWebFormSplitTests();
    }

    public function testShouldReturnArray() {
        $this->assertTrue(is_array($this->forms));
    }

    public function testShouldHaveCorrectCountOfEntries() {
        $this->assertEqual(sizeOf($this->forms), 10);
    }

    public function testShouldHaveEntries() {
        foreach($this->forms as $entry) {
            $this->assertTrue(is_a($entry, 'AWeberEntry'));
        }
    }

    public function testShouldHaveFullURL() {
        foreach($this->forms as $entry) {
            $this->assertTrue(preg_match('/^\/accounts\/1\/lists\/[0-9]*\/web_form_split_tests\/[0-9]*$/', $entry->url));
        }
    }
}

class TestAccountFindSubscribers extends AccountTestCase {

    public function testShouldSupportFindSubscribersMethod() {
        $subscribers = $this->entry->findSubscribers(array('email' => 'joe@example.com'));

        $this->assertTrue(is_a($subscribers, 'AWeberCollection'));
        $this->assertEqual(count($subscribers), 1);
        $this->assertEqual($subscribers->data['entries'][0]['self_link'],
                           'https://api.aweber.com/1.0/accounts/1/lists/303449/subscribers/1');
    }
}

class TestAWeberSubscriberEntry extends UnitTestCase {

    public function setUp() {
        $this->adapter = new MockOAuthAdapter();
        $this->adapter->app = new AWeberServiceProvider();
        $url = '/accounts/1/lists/303449/subscribers/1';
        $data = $this->adapter->request('GET', $url);
        $this->entry = new AWeberEntry($data, $url, $this->adapter);
    }

    public function testIsSubscriber() {
        $this->assertEqual($this->entry->type, 'subscriber');
    }

    public function testHasCustomFields() {
        $fields = $this->entry->custom_fields;
        $this->assertFalse(empty($fields));
    }

    public function testCanReadCustomFields() {
        $this->assertEqual($this->entry->custom_fields['Color'], 'blue');
        $this->assertEqual($this->entry->custom_fields['Walruses'], '32');
    }

    public function testCanUpdateCustomFields() {
        $this->entry->custom_fields['Color'] = 'Jeep';
        $this->entry->custom_fields['Walruses'] = 'Cherokee';
        $this->assertEqual($this->entry->custom_fields['Color'], 'Jeep');
    }

    public function testCanViewSizeOfCustomFields() {
        $this->assertEqual(sizeOf($this->entry->custom_fields), 6);
    }

    public function testCanIterateOverCustomFields() {
        $count = 0;
        foreach ($this->entry->custom_fields as $field => $value) {
            $count++;
        }
        $this->assertEqual($count, sizeOf($this->entry->custom_fields));
    }

    public function testShouldBeUpdatable() {
        $this->adapter->clearRequests();
        $this->entry->custom_fields['Color'] = 'Jeep';
        $this->entry->save();
        $data = $this->adapter->requestsMade[0]['data'];
        $this->assertEqual($data['custom_fields']['Color'], 'Jeep');
    }
}

class TestAWeberMoveEntry extends UnitTestCase {

    public function setUp() {
        $this->adapter = new MockOAuthAdapter();
        $this->adapter->app = new AWeberServiceProvider();

         # Get Subscriber
         $url = '/accounts/1/lists/303449/subscribers/1';
         $data = $this->adapter->request('GET', $url);
         $this->subscriber = new AWeberEntry($data, $url, $this->adapter);

         $url = '/accounts/1/lists/303449/subscribers/2';
         $data = $this->adapter->request('GET', $url);
         $this->unsubscribed = new AWeberEntry($data, $url, $this->adapter);

         # Different List
         $url = '/accounts/1/lists/505454';
         $data = $this->adapter->request('GET', $url);
         $this->different_list = new AWeberEntry($data, $url, $this->adapter);
    }

    /**
     * Move Succeeded
     */
    public function testMove_Success() {

         $this->adapter->clearRequests();
         $resp = $this->subscriber->move($this->different_list);

         $this->assertEqual(sizeOf($this->adapter->requestsMade), 2);

         $req = $this->adapter->requestsMade[0];
         $this->assertEqual($req['method'], 'POST');
         $this->assertEqual($req['uri'], $this->subscriber->url);
         $this->assertEqual($req['data'], array(
             'ws.op' => 'move',
             'list_link' => $this->different_list->self_link));

         $req = $this->adapter->requestsMade[1];
         $this->assertEqual($req['method'], 'GET');
         $this->assertEqual($req['uri'], '/accounts/1/lists/505454/subscribers/3');
     }

    /**
     * Move Failed
     */
     public function testMove_Failure() {

         $this->adapter->clearRequests();
         $resp = $this->unsubscribed->move($this->different_list);

         $this->assertEqual(sizeOf($this->adapter->requestsMade), 1);

         $req = $this->adapter->requestsMade[0];
         $this->assertEqual($req['method'], 'POST');
         $this->assertEqual($req['uri'], $this->unsubscribed->url);
         $this->assertEqual($req['data'], array(
             'ws.op' => 'move',
             'list_link' => $this->different_list->self_link));

         $this->assertFalse($resp);
         return;
    }
}

class TestGettingEntryParentEntry extends UnitTestCase {

    public function setUp() {
        $this->adapter = new MockOAuthAdapter();
        $this->adapter->app = new AWeberServiceProvider();
        $url = '/accounts/1/lists/303449';
        $data = $this->adapter->request('GET', $url);
        $this->list = new AWeberEntry($data, $url, $this->adapter);
        $url = '/accounts/1';
        $data = $this->adapter->request('GET', $url);
        $this->account = new AWeberEntry($data, $url, $this->adapter);
        $url = '/accounts/1/lists/303449/custom_fields/1';
        $data = $this->adapter->request('GET', $url);
        $this->customField = new AWeberEntry($data, $url, $this->adapter);
    }

    public function testListParentShouldBeAccount() {
        $entry = $this->list->getParentEntry();
        $this->assertTrue(is_a($entry, 'AWeberEntry'));
        $this->assertEqual($entry->type, 'account');
    }

    public function testCustomFieldParentShouldBeList() {
        $entry = $this->customField->getParentEntry();
        $this->assertTrue(is_a($entry, 'AWeberEntry'));
        $this->assertEqual($entry->type, 'list');
    }

    public function testAccountParentShouldBeNULL() {
        $entry = $this->account->getParentEntry();
        $this->assertEqual($entry, NULL);
    }
}
