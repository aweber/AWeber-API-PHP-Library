AWeber API PHP Library
======================

PHP library for easily integrating with the AWeber API.

Changelog:
----------
2011-12-23: v1.1.2

 * Fixed a bug in the AWeberCollection class to properly set the URL of entries found in collections.

2011-10-10: v1.1.1

 * Raise an E_USER_WARNING instead of a fatal error if multiple instances of the client library are installed.

2011-08-29: v1.1.0

 * Modified client library to raise an AWeberAPIException on any API errors (HTTP status >= 400)
 * Refactored tests for better code coverage
 * Refactored move and create methods to return the resource or raise an AWeberAPIException on error.
 * Added getActivity method to a subscriber entry.

Basic Usage:
------------
Refer to demo.php to see a working example of how to authenticate an app and query the API.

For more complete documentation please refer to:
https://labs.aweber.com/docs/php-library-walkthrough


Handling Errors:
----------------
Sometimes errors happen and your application should handle them appropriately.
Whenever an API error occurs an AWeberAPIException will be raised with a detailed
error message and documentation link to explain what's wrong.

You should wrap any calls to the API in a try/except block.

Common Errors:

 * Resource not found (404 error)
 * Your application has been rate limited (403 error)
 * Bad request (400 error)
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

Running Tests:
--------------
Testing the PHP api library requires installation of a few utilities.

### Requirements ###
[Apache Ant](http://ant.apache.org/) is used to run the build targets in the build.xml file. Get the latest version.

Setup `/etc/php.ini` configuration file. Make sure `include_path` contains the correct directories.(`/usr/lib/php` on MacOS) Set `date.timezone` to your local timezone.

[PHP PEAR](http://pear.php.net/manual/en/installation.getting.php) is used to install utilities for testing and code metrics. Upgrade to the latest version using `sudo pear upgrade pear`.

[PHP QA Tools](http://pear.phpqatools.org/) contain a collection of PHP testing tools. The following commands were needed to install:

```bash
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
