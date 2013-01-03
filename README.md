AWeber API PHP Library [![Build Status](https://secure.travis-ci.org/aweber/AWeber-API-PHP-Library.png?branch=master)](http://travis-ci.org/aweber/AWeber-API-PHP-Library)
======================

PHP library for easily integrating with the AWeber API.


Basic Usage:
------------
Refer to demo.php to see a working example of how to authenticate an app and query the API.

For more complete documentation please refer to:
https://labs.aweber.com/docs/php-library-walkthrough


Handling Errors:
----------------
Sometimes errors happen and your application should handle them appropriately.
Whenever an API error occurs an AWeberAPIException will be raised with a detailed
error message and documentation link to explain what is wrong.

You should wrap any calls to the API in a try/except block.

Common Errors:
 * Bad request (400 error)
 * Your application is not authorized (401 error)
 * Your application has been rate limited (403 error)
 * Resource not found (404 error)
 * API Temporarily unavailable (503 error)

Refer to https://labs.aweber.com/docs/troubleshooting for the complete list

Example Below:

```php
<?php

$consumerKey = '***';
$consumerSecret = '***';
$accessKey = '***';
$accessSecret = '***';

$aweber = new AWeberAPI($consumerKey, $consumerSecret);
$account = $aweber->getAccount($accessKey, $accessSecret);

try {
    $resource = $account->loadFromUrl('/accounts/idontexist');
} catch (AWeberAPIException $exc) {
    print "<li> $exc->type on $exc->url, refer to $exc->message for more info ...<br>";
}
?>
```


Accessing personally identifiable subscriber data
-------------------------------------------------
In order to view or update the email, name, misc_notes, and ip_address fields of a subscriber, your app must
specifically request access to subscriber data.   Refer to our documentation at
https://labs.aweber.com/docs/permissions for more information on how to be able to access personally identifiable
subscriber information.


Changelog:
----------
2013-01-03: v1.1.9
  * Updated client library to support 1.0.17 of the API. (Broadcast Statistics)

2012-12-13: v1.1.8
  * Fixed a bug that resulted in Exceptions being raised when using collections when the collection size is zero.

2012-12-10: v1.1.7
  * Added a parameter to the Move Subscriber method for last followup message number sent.
    * to support version 1.0.16 of the API.  See https://labs.aweber.com/docs/changelog

2012-09-19: v1.1.6
  * Fixed a bug that prevented resource attributes from being saved when the initial value of the resource attribute was null.
    * used array_key_exists instead of isset for evaluation of associative arrays.  Requires PHP >= 4.0.7

2012-07-05: v1.1.5
  * Fixed a bug were a utf8_encode notice was raised when updating subscriber custom field values.

2012-05-08: v1.1.4
   Some API Developers have reported AWeberOAuthDataMissing exceptions when using the demo.php script.
   This error message is not helpful as the typical cause for this exception is an invalid consumer key or secret.

   The client library has been refactored to always raise an AWeberAPIException when a 40x/50x http status code
   response is returned.  This exception will clearly indicate the cause of the error for easier troubleshooting.
 * Refactored makeRequest to always raise an AWeberAPIException when a 40x or 50x status is returned.
 * Refactored makeRequest to indicate transient networking or firewall connectivity issues.
 * Refactored mock adaptor makeRequest for testing to behave the same way as the real makeRequest does.

2012-04-18: v1.1.3

 * Removed usage of deprecated split function.

2011-12-23: v1.1.2

 * Fixed a bug in the AWeberCollection class to properly set the URL of entries found in collections.

2011-10-10: v1.1.1

 * Raise an E_USER_WARNING instead of a fatal error if multiple instances of the client library are installed.

2011-08-29: v1.1.0

 * Modified client library to raise an AWeberAPIException on any API errors (HTTP status >= 400)
 * Refactored tests for better code coverage
 * Refactored move and create methods to return the resource or raise an AWeberAPIException on error.
 * Added getActivity method to a subscriber entry.



Running Tests:
--------------
Testing the PHP api library requires installation of a few utilities.

### Requirements ###
[Apache Ant](http://ant.apache.org/) is used to run the build targets in the build.xml file. Get the latest version.

Setup `/etc/php.ini` configuration file. Make sure `include_path` contains the correct directories.(`/usr/lib/php` on MacOS) Set `date.timezone` to your local timezone.

[PHP PEAR](http://pear.php.net/manual/en/installation.getting.php) is
used to install utilities for testing and code metrics. Upgrade to the
latest version using `sudo pear upgrade pear`.

[PHP QA Tools](http://pear.phpqatools.org/) contain a collection of PHP testing tools. The following commands were needed to install:

```bash
sudo pear upgrade
sudo pear config-set auto_discover 1
sudo pear channel-discover pear.phpqatools.org
sudo pear install phpqatools/phpqatools
```

If installing PHP QA Tools fails due to missing channels, manually add the missing channels:

```bash
sudo pear channel-discover pear.phpunit.de
sudo pear channel-discover pear.pdepend.org
sudo pear channel-discover pear.phpmd.org
sudo pear channel-discover components.ez.no
sudo pear channel-discover pear.symfony-project.com
```

### Execute Tests ###
Once the above requirements are installed, run the tests from the base
directory using the command: `ant phpunit`.

To run the entire suite of checks and tests, run: `ant`.
