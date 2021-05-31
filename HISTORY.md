
1.8.0 / 2021-05-31
==================
  
  * Fix socket return response (#174)
  * API Endpoint update (#168)
  * Update Batch Size Check (#168)
  * Remove messageID override capabilities (#163)
  * Update flush sleep waiting period (#161)

1.7.0 / 2021-03-10
=======================
  
  * Retry Network errors (#136)
  * Update Tests [Improvement] (#132)
  * Updtate Readme Status Badging (#139)
  * Bump e2e tests to latest version [Improvement] (#142)
  * Add Limits to message, batch and memeory usage [Feature] (#137)
  * Add Configurable flush parameters [Feature] (#135)
  * Add ability to use custom consumer [Feature] (#61)
  * Add ability to set file permmissions [Feature] (#122)
  * Fix curl error handler [Improvement] (#97)
  * Fix timestamp implementation for microseconds (#94)
  * Modify max queue size setting to match requirements (#153, #146)
  * Add ability to set userid as zero (#157)


1.6.1-beta / 2018-05-01
=======================

  * Fix tslint error in version.php

1.6.0-beta / 2018-04-30
=======================

  * Add License file
  * Coding style fixers (#112)
  * rename type to method to match new harness contract (#110)
  * Increase Code coverage (#108)
  * Add Linter to CI (#109)
  * When the message size is larger than 32KB, return FALSE instead of throw exception
  * Make writeKey required as a flag in the CLI instead of as an environment variable.
  * Verify message size is below than 32KB
  * Run E2E test when RUN_E2E_TESTS is defined
  * Add Rfc 7231 compliant user agent into request header
  * Add backoff for socket communication
  * Implement response error handling for POST request and add backoff (in LibCurl)
  * Change environment to precise as default
  * CI: Make PHP 5.3 test to be run in precise environment
  * Make host to be configurable
  * Add anonymousId in group payload

1.5.2 / 2017-08-18
==================

  * Always set default context.

1.5.1 / 2017-04-06
==================

  * Use require_once() instead of require(). Fixes issue where seperate plugins in systems such as Moodle break because of class redeclaration when using seperate internal versions of Segment.io.

1.5.0 / 2017-03-03
==================

  * Adding context.library.consumer to all PHP events
  * libcurl consumer will retry once if http response is not 200
  * update link to php docs
  * improve portability and reliability of Makefile accros different platforms (#74)

1.4.2 / 2016-07-11
==================

  * remove the extra -e from echo in makefile

1.4.1 / 2016-07-11
==================

  * use a more portable shebang

1.4.0 / 2016-07-11
==================

  * adding a simple wrapper CLI
  * explicitly declare library version in global scope during creating new release to allow using library with custom autoload (composer for example)

1.3.0 / 2016-04-05
==================

  * Introducing libcurl consumer
  * Change Consumer to protected instead of private

1.2.7 / 2016-03-04
==================

  * adding global

1.2.6 / 2016-03-04
==================

  * fix version

1.2.5 / 2016-03-04
==================

  * Adding release script, fixing version
  * Pass back ->flush() result to allow caller code know if flushed successfully

1.2.4 / 2016-02-17
=============

  * core: fix error name
  * send: make send.php executable
  * socket: adding fix for FIN packets from remote

1.2.3 / 2016-02-01
==================

  * instead of using just is_int and is_float for checking timestamp, use filter_var since that can detect string ints and floats - if its not a string or float, consider it might be a ISO8601 or some other string, so use strtotime() to support other strings

1.2.1 / 2015-12-29
==================

  * socket open error checking fix
  * Fix batch size check before flushing tracking queue
  * Fix bug in send.php

1.2.0 / 2015-04-27
==================

 * removing outdated test
 * enabling ssl by default
 * socket: bump timeout to 5s

1.1.3 / 2015-03-03
==================

  * formatTime: use is_* and fix to support floats


1.1.2 / 2015-03-03
==================

  * send.php: fix error handling
  * client: fix float timestamp handling


1.1.1 / 2015-02-11
==================

  * Add updated PHP version requirement for @phpunit
  * add .sentAt


1.1.0 / 2015-01-07
==================

  * support microtime
  * Update README.md
  * drop the io

1.0.3 / 2014-10-14
==================

 * fix: empty array for traits and properties

1.0.2 / 2014-09-29
==================

 * fix: identify(), group() with empty traits
 * suppressing logs generated when attempting to write to a reset socket [j0ew00ds]
 * Added PHP 5.6, 5.5 and HHVM to travis.yml [Nyholm]

1.0.1 / 2014-09-16
==================

 * fixing validation for Segment::page() calls
 * updating send.php error message
 * fix send.php to exit gracefully when there is no log file to process

1.0.0 / 2014-06-16
==================

 * update to work with new spec
 * add ./composer.phar validation test
 * better send.php output
 * add validation
 * use strtotime in send.php and support php5.3
 * rename Analytics to Segment
 * add send.php to replace file_reader.py
 * add new methods implementation and tests
 * implement spec changes
 * change tests to reflect spec changes
 * test changes:
 * Fix typo in composer.json

0.4.8 / 8-21-2013
=============
* adding fix for socket requests which might complete in multiple fwrites

0.4.7 / 5-28-2013
=============
* `chmod` the log file to 0777 so that the file_reader.py can read it

0.4.6 / 5-25-2013
=============
* Check for status existing on response thanks to [@gmoreira](https://github.om/gmoreira)

0.4.5 / 5-20-2013
=============
* Check for empty secret thanks to [@mustela](https://github.com/mustela).

0.4.3 / 5-1-2013
=============
* Make file_reader rename to a file in the same directory as the log file thanks to [@marshally](https://github.com/marshally)

0.4.2 / 4-26-2013
=============
* Fix for $written var on connection error thanks to [@gmoreira](https://github.com/gmoreira)

0.4.1 / 4-25-2013
=============
* Adding fix to file_reader alias

0.4.0 / 4-8-2013
=============
* Full Autoloading an PEAR naming by [Cethy](https://github.com/Cethy)
* Adding alias call

0.3.0 / 3-22-2013
=============
* Adding try-catch around fwrite cal

0.2.7 / 3-17-2013
=============
* Adding file_reader.py fix

0.2.6 / 3-15-2013
=============
* Rename analytics.php -> Analytics.php to allow autoloading by [Cethy](https://github.com/Cethy)

0.2.5 / 2-22-2013
=============
* Trailing whitespace/end php tags fix by [jimrubenstein](https://github.com/jimrubenstein)

0.2.4 / 2-19-2013
=============
* Support fwrite retry on closed socket.

0.2.3 / 2-12-2013
=============
* Adding check for count in properties and traits length.

0.2.2 / 2-11-2013
=============
* Adding default args for properties

0.2.1 / 2-1-2013
=============
* Enabling pfsockopen for persistent connections
* Making socket default

0.2.0 / 2-1-2013
=============
* Updating consumer class to use shared functions.
* Removed *fork* consumer, renamed *fork_queue* to *fork_curl*.

0.1.1 / 1-30-2013
=============
* Adding fork consumer
* Adding fork_queue consumer
* Setting fork_queue consumer to be the default.

0.1.0 / 1-29-2013
=============

Initial version
