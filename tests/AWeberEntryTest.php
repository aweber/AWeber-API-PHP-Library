<?php
require_once('aweber_api/aweber_api.php');
require_once('mock_adapter.php');

class TestAWeberEntry extends PHPUnit_Framework_TestCase {

    /**
     * Before each test, sets up mock adapter to fake requests with fixture
     * data and AWeberEntry based on list 303449
     */
    public function setUp() {
        $this->adapter = get_mock_adapter();
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
        $this->assertEquals($this->entry->id, 303449);
    }

    /**
     * Should be able to access name (or any property unique to the response)
     */
    public function testShouldBeAbleToAccessName() {
        $this->assertEquals($this->entry->name, 'default303449');
    }

    /**
     * Should be able to discern its type based on its data
     */
    public function testShouldKnowItsType() {
        $this->assertEquals($this->entry->type, 'list');
    }

    /**
     * When access properties it does not have, but are known sub collections,
     * it will request for it and return the new collection object.
     */
    public function testShouldProvidedCollections() {
        $this->adapter->clearRequests();
        $campaigns = $this->entry->campaigns;

        $this->assertTrue(is_a($campaigns, 'AWeberCollection'));
        $this->assertEquals(count($this->adapter->requestsMade), 1);
        $this->assertEquals($this->adapter->requestsMade[0]['uri'],
            '/accounts/1/lists/303449/campaigns');
    }

    /**
     * When accessing non-implemented children of a resource, should raised
     * a not implemented exception
     */
    public function testShouldThrowExceptionIfNotImplemented() {
        $this->adapter->clearRequests();
        $this->setExpectedException('AWeberResourceNotImplemented');
        $obj = $this->entry->something_not_implemented;
        $this->assertEquals(count($this->adapter->requestsMade), 0);
    }

    /**
     * Should return the name of all attributes and collections in this entry
     */
    public function testAttrs() {
        $this->assertEquals($this->entry->attrs(),
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
        $this->assertSame($resp, true);
        $this->assertEquals(sizeOf($this->adapter->requestsMade), 1);
        $this->assertEquals($this->adapter->requestsMade[0]['method'], 'DELETE');
        $this->assertEquals($this->adapter->requestsMade[0]['uri'], $this->entry->url);
    }

    /**
     * When delete returns a non-200 status code, the delete failed and false is
     * returned.
     */
    public function testFailedDelete() {
        $url = '/accounts/1';
        $data = $this->adapter->request('GET', $url);
        $entry = new AWeberEntry($data, $url, $this->adapter);

        $this->setExpectedException('AWeberAPIException', 'Simulated Exception');
        $entry->delete();
    }

    /**
     *  Should be able to change a property in an entry's data array directly on
     *  the object, and have that change propogate to its data array
     *
     */
    public function testSet() {
        $this->assertNotEquals($this->entry->name, 'mynewlistname');
        $this->assertNotEquals($this->entry->data['name'], 'mynewlistname');
        $this->entry->name = 'mynewlistname';
        $this->assertEquals($this->entry->name, 'mynewlistname');
        $this->assertEquals($this->entry->data['name'], 'mynewlistname');
    }

    /**
     * Should Color a request when a save is made.
     */
    public function testSave() {
        $this->entry->name = 'mynewlistname';
        $this->adapter->clearRequests();
        $resp = $this->entry->save();
        $this->assertEquals(sizeOf($this->adapter->requestsMade), 1);
        $req = $this->adapter->requestsMade[0];
        $this->assertEquals($req['method'], 'PATCH');
        $this->assertEquals($req['uri'], $this->entry->url);
        $this->assertEquals($req['data'], array('name' => 'mynewlistname'));
        $this->assertSame($resp, true);
    }

    public function testSaveFailed() {
        $url = '/accounts/1/lists/505454';
        $data = $this->adapter->request('GET', $url);
        $entry = new AWeberEntry($data, $url, $this->adapter);
        $entry->name = 'foobarbaz';
        $this->setExpectedException('AWeberAPIException', 'Simulated Exception');
        $resp = $entry->save();
    }

    /**
     * Should keep track of whether or not this entry is "dirty".  It should
     * not issue save calls if it hasn't been altered since the last successful
     * load / save operation.
     */
    public function testShouldMaintainDirtiness() {
        $this->adapter->clearRequests();
        $resp = $this->entry->save();
        $this->assertEquals(sizeOf($this->adapter->requestsMade), 0);
        $this->entry->name = 'mynewlistname';
        $resp = $this->entry->save();
        $this->assertEquals(sizeOf($this->adapter->requestsMade), 1);
        $resp = $this->entry->save();
        $this->assertEquals(sizeOf($this->adapter->requestsMade), 1);
    }


}

abstract class AccountTestCase extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->adapter = get_mock_adapter();
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
 * @uses PHPUnit_Framework_TestCase
 * @package
 * @version $id$
 */
class TestAWeberAccountEntry extends AccountTestCase {

    public function testIsAccount() {
        $this->assertEquals($this->entry->type, 'account');
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
        $this->assertEquals(sizeOf($this->forms), 181);
    }

    public function testShouldHaveEntries() {
        foreach($this->forms as $entry) {
            $this->assertTrue(is_a($entry, 'AWeberEntry'));
        }
    }

    public function testShouldHaveFullURL() {
        foreach($this->forms as $entry) {
          $this->assertEquals(preg_match('/^\/accounts\/1\/lists\/[0-9]*\/web_forms\/[0-9]*$/', $entry->url), 1);
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
        $this->assertEquals(sizeOf($this->forms), 10);
    }

    public function testShouldHaveEntries() {
        foreach($this->forms as $entry) {
            $this->assertTrue(is_a($entry, 'AWeberEntry'));
        }
    }

    public function testShouldHaveFullURL() {
        foreach($this->forms as $entry) {
          $this->assertEquals(preg_match('/^\/accounts\/1\/lists\/[0-9]*\/web_form_split_tests\/[0-9]*$/', $entry->url), 1);
        }
    }
}

class TestAccountFindSubscribers extends AccountTestCase {

    public function testShouldSupportFindSubscribersMethod() {
        $subscribers = $this->entry->findSubscribers(array('email' => 'joe@example.com'));
        $this->assertTrue(is_a($subscribers, 'AWeberCollection'));
        $this->assertEquals(count($subscribers), 1);
        $this->assertEquals($subscribers->data['entries'][0]['self_link'],
                           'https://api.aweber.com/1.0/accounts/1/lists/303449/subscribers/1');
    }
}

class TestAWeberSubscriberEntry extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->adapter = get_mock_adapter();
        $url = '/accounts/1/lists/303449/subscribers/1';
        $data = $this->adapter->request('GET', $url);
        $this->entry = new AWeberEntry($data, $url, $this->adapter);
    }

    public function testIsSubscriber() {
        $this->assertEquals($this->entry->type, 'subscriber');
    }

    public function testHasCustomFields() {
        $fields = $this->entry->custom_fields;
        $this->assertFalse(empty($fields));
    }

    public function testCanReadCustomFields() {
        $this->assertEquals($this->entry->custom_fields['Color'], 'blue');
        $this->assertEquals($this->entry->custom_fields['Walruses'], '32');
    }

    public function testCanUpdateCustomFields() {
        $this->entry->custom_fields['Color'] = 'Jeep';
        $this->entry->custom_fields['Walruses'] = 'Cherokee';
        $this->assertEquals($this->entry->custom_fields['Color'], 'Jeep');
    }

    public function testCanViewSizeOfCustomFields() {
        $this->assertEquals(sizeOf($this->entry->custom_fields), 6);
    }

    public function testCanIterateOverCustomFields() {
        $count = 0;
        foreach ($this->entry->custom_fields as $field => $value) {
            $count++;
        }
        $this->assertEquals($count, sizeOf($this->entry->custom_fields));
    }

    public function testShouldBeUpdatable() {
        $this->adapter->clearRequests();
        $this->entry->custom_fields['Color'] = 'Jeep';
        $this->entry->save();
        $data = $this->adapter->requestsMade[0]['data'];
        $this->assertEquals($data['custom_fields']['Color'], 'Jeep');
    }

    public function testShouldSupportGetActivity() {
        $activity = $this->entry->getActivity();
        $this->assertTrue(is_a($activity, 'AWeberCollection'));
        $this->assertEquals($activity->total_size, 1);
    }
}

class TestAWeberMoveEntry extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->adapter = get_mock_adapter();

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

         $this->assertEquals(sizeOf($this->adapter->requestsMade), 2);

         $req = $this->adapter->requestsMade[0];
         $this->assertEquals($req['method'], 'POST');
         $this->assertEquals($req['uri'], $this->subscriber->url);
         $this->assertEquals($req['data'], array(
             'ws.op' => 'move',
             'list_link' => $this->different_list->self_link));

         $req = $this->adapter->requestsMade[1];
         $this->assertEquals($req['method'], 'GET');
         $this->assertEquals($req['uri'], '/accounts/1/lists/505454/subscribers/3');
     }

    /**
     * Move Failed
     */
     public function testMove_Failure() {

         $this->adapter->clearRequests();
         $this->setExpectedException('AWeberAPIException', 'Simulated Exception');
         $this->unsubscribed->move($this->different_list);
         $this->assertEquals(sizeOf($this->adapter->requestsMade), 1);

         $req = $this->adapter->requestsMade[0];
         $this->assertEquals($req['method'], 'POST');
         $this->assertEquals($req['uri'], $this->unsubscribed->url);
         $this->assertEquals($req['data'], array(
             'ws.op' => 'move',
             'list_link' => $this->different_list->self_link));
         return;
     }

     /**
     * Move with LastMessageSentNumber Succeeded
     */
    public function testMoveWLastMessageNumberSent_Success() {
         $this->last_followup_message_number_sent = 1;

         $this->adapter->clearRequests();
         $resp = $this->subscriber->move($this->different_list, $this->last_followup_message_number_sent);

         $this->assertEquals(sizeOf($this->adapter->requestsMade), 2);

         $req = $this->adapter->requestsMade[0];
         $this->assertEquals($req['method'], 'POST');
         $this->assertEquals($req['uri'], $this->subscriber->url);
         $this->assertEquals($req['data'], array(
             'ws.op' => 'move',
             'list_link' => $this->different_list->self_link,
             'last_followup_message_number_sent' => $this->last_followup_message_number_sent));

         $req = $this->adapter->requestsMade[1];
         $this->assertEquals($req['method'], 'GET');
         $this->assertEquals($req['uri'], '/accounts/1/lists/505454/subscribers/3');
     }

}

class TestGettingEntryParentEntry extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->adapter = get_mock_adapter();
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
        $this->assertEquals($entry->type, 'account');
    }

    public function testCustomFieldParentShouldBeList() {
        $entry = $this->customField->getParentEntry();
        $this->assertTrue(is_a($entry, 'AWeberEntry'));
        $this->assertEquals($entry->type, 'list');
    }

    public function testAccountParentShouldBeNULL() {
        $entry = $this->account->getParentEntry();
        $this->assertEquals($entry, NULL);
    }
}
