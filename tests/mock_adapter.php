<?php
require_once('mock_data.php');

function get_mock_adapter() {
    // function to return a mock adapter
    $serviceProvider = new AWeberServiceProvider();
    return new MockOAuthAdapter($serviceProvider);
}

$map = array();
$map['DELETE']['/accounts/1'                                                                                    ] = array(403, 'error');
$map['DELETE']['/accounts/1/lists/303449'                                                                       ] = array(200, null);

$map['GET'   ]['/accounts'                                                                                      ] = array(200, 'accounts/page1');
$map['GET'   ]['/accounts/1'                                                                                    ] = array(200, 'accounts/1');
$map['GET'   ]['/accounts/1/lists'                                                                              ] = array(200, 'lists/page1');
$map['GET'   ]['/accounts/1/lists/303449'                                                                       ] = array(200, 'lists/303449');
$map['GET'   ]['/accounts/1/lists/303449/campaigns'                                                             ] = array(200, 'campaigns/303449');
$map['GET'   ]['/accounts/1/lists/303449/custom_fields'                                                         ] = array(200, 'custom_fields/303449');
$map['GET'   ]['/accounts/1/lists/303449/custom_fields/1'                                                       ] = array(200, 'custom_fields/1');
$map['GET'   ]['/accounts/1/lists/303449/custom_fields/2'                                                       ] = array(200, 'custom_fields/2');
$map['GET'   ]['/accounts/1/lists/303449/subscribers'                                                           ] = array(200, 'subscribers/page1');
$map['GET'   ]['/accounts/1/lists/303449/subscribers/1'                                                         ] = array(200, 'subscribers/1');
$map['GET'   ]['/accounts/1/lists/303449/subscribers/1?ws.op=getActivity'                                       ] = array(200, 'subscribers/activity');
$map['GET'   ]['/accounts/1/lists/303449/subscribers/1?ws.op=getActivity&ws.show=total_size'                    ] = array(200, 'subscribers/activity_ts');
$map['GET'   ]['/accounts/1/lists/303449/subscribers/2'                                                         ] = array(200, 'subscribers/2');
$map['GET'   ]['/accounts/1/lists/303449/subscribers?email=nonexist%40example.com&ws.op=find&ws.show=total_size'] = array(200, 'subscribers/nonexist_tsl');
$map['GET'   ]['/accounts/1/lists/303449/subscribers?email=nonexist%40example.com&ws.op=find'                   ] = array(200, 'subscribers/nonexist');
$map['GET'   ]['/accounts/1/lists/303449/subscribers?email=someone%40example.com&ws.op=find&ws.show=total_size' ] = array(200, 'subscribers/find_tsl');
$map['GET'   ]['/accounts/1/lists/303449/subscribers?email=someone%40example.com&ws.op=find'                    ] = array(200, 'subscribers/find');
$map['GET'   ]['/accounts/1/lists/505454'                                                                       ] = array(200, 'lists/505454');
$map['GET'   ]['/accounts/1/lists/505454/subscribers/3'                                                         ] = array(200, 'subscribers/3');
$map['GET'   ]['/accounts/1/lists?ws.start=20&ws.size=20'                                                       ] = array(200, 'lists/page2');
$map['GET'   ]['/accounts/1?email=joe%40example.com&ws.op=findSubscribers&ws.show=total_size'                   ] = array(200, 'accounts/findSubscribers_ts');
$map['GET'   ]['/accounts/1?email=joe%40example.com&ws.op=findSubscribers'                                      ] = array(200, 'accounts/findSubscribers');
$map['GET'   ]['/accounts/1?ws.op=getWebFormSplitTests'                                                         ] = array(200, 'accounts/webFormSplitTests');
$map['GET'   ]['/accounts/1?ws.op=getWebForms'                                                                  ] = array(200, 'accounts/webForms');
$map['GET'   ]['/accounts/1/lists/303449/subscribers?email=someone%40example.com&ws.show=total_size'            ] = array(200, 'empty');

# collection pagination tests
$map['GET'   ]['/accounts/1/lists/303449/subscribers?status=unsubscribed&ws.size=1&ws.start=0&ws.op=find'                   ] = array(200, 'subscribers/find_1of2');
$map['GET'   ]['/accounts/1/lists/303449/subscribers?status=unsubscribed&ws.size=1&ws.start=1&ws.op=find'                   ] = array(200, 'subscribers/find_2of2');
$map['GET'   ]['/accounts/1/lists/303449/subscribers?status=unsubscribed&ws.size=1&ws.start=0&ws.op=find&ws.show=total_size'] = array(200, 'subscribers/find_1of2_tsl');

$map['PATCH' ]['/accounts/1/lists/303449'                                                                       ] = array(209, 'lists/303449');
$map['PATCH' ]['/accounts/1/lists/303449/subscribers/1'                                                         ] = array(209, 'subscribers/1');
$map['PATCH' ]['/accounts/1/lists/505454'                                                                       ] = array(404, 'error');

$map['POST'  ]['/accounts/1/lists/303449/custom_fields'                                                         ] = array(201, '/accounts/1/lists/303449/custom_fields/2');
$map['POST'  ]['/accounts/1/lists/303449/subscribers/1'                                                         ] = array(201, '/accounts/1/lists/505454/subscribers/3');
$map['POST'  ]['/accounts/1/lists/303449/subscribers/2'                                                         ] = array(400, 'error');


class MockOAuthAdapter extends OAuthApplication {

    public $requestsMade = array();

    public function addRequest($method, $uri, $data) {
        $this->requestsMade[] = array(
            'method' => $method,
            'uri'    => $uri,
            'data'   => $data);
    }

    public function clearRequests() {
        $this->requestsMade = array();
    }

    public function makeRequest($method, $url, $data=array()) {
        global $map;

        # append params to url (for fixtures)
        $uri = str_replace($this->app->baseUri, '', $url);
        if ($method == 'GET' && !empty($data)) {
            $uri = $uri.'?'. http_build_query($data);
        }

        # extract response map parameters
        #
        $status = $map[$method][$uri][0];
        $resource = $map[$method][$uri][1];

        # record the request
        $this->addRequest($method, $uri, $data);

        # load response from fixture and return data
        $mock_data = MockData::load($resource);
        if (!$mock_data) {
            $msg  = 'Unable to connect to the AWeber API.  Please ensure that CURL is enabled and your ';
            $msg .= 'firewall allows outbound SSL requests from your web server.';
            $error = array('message' => $msg, 'type' => 'APIUnreachableError',
                           'documentation_url' => 'https://labs.aweber.com/docs/troubleshooting');
            throw new AWeberAPIException($error, $url);
        }

        $headers = array();
        $headers['Status-Code'] = $status;

        if($status == 201) {
            $headers['Location'] = $resource;
        }
        $mock_data->headers = $headers;

        if($headers['Status-Code'] >= 400) {
            $data = json_decode($mock_data->body, true);
            throw new AWeberAPIException($data['error'], $url);

        }
        return $mock_data;
    }
}
