2.13.0
===========
* [Improvement] Add PHP 8.5 support; widen constraint to ~8.2.0|~8.3.0|~8.4.0|~8.5.0
* [Improvement] Add PHPCompatibility (phpcs) cross-version check for PHP 8.2-8.5
* [Improvement] Bump Rector target to PHP 8.5 set
* [Internal] Add GitHub Actions CI (lint, phpcs, rector, phpunit matrix 8.4-8.5)

2.11.4
===========
* [Bugfix] Resolve packages even if in the absolute path contains 'test' keyword
* [Improvement] Prepare tests infrastructure for the future: add phpunit to composer.json and tests/sandbox folder for M2 sandbox.

2.11.1
===========
* [Bugfixes fixes for Disassembling analysis](https://vconnect.atlassian.net/browse/IE-309)

2.11.0
===========
* [Changes to Disassembling algorithm](https://vconnect.atlassian.net/browse/IE-302)

2.10.2
===========
Fixes:
* [Fix run with composer packages that are placed under subdirectories](https://vconnect.atlassian.net/browse/IE-305)

2.10.1
===========
Fixes:
* [Fix broken disassemble command](https://vconnect.atlassian.net/browse/IE-301)

2.10.0
===========
Internal framework changes:
* Implement caching mechanism based on composer plugin

2.9.0
===========
New features:
* [Layout dependencies scanner](https://vconnect.atlassian.net/browse/IE-278)

2.8.0
===========
New features:
* [Reporting notices about potential redundant dependencies](https://vconnect.atlassian.net/browse/IE-274) in `composer.json` and `module.xml` files:
    * It is a notice report level, meaning that it is not treated as defect and returns 0 exit code;
* [Detecting dependencies between extension that declares url route and extension that uses it;](https://vconnect.atlassian.net/browse/IE-273)
  * It scans PHP files for `->getUrl()` calls to get the requested route URL path and find its dependency;
* [Wildcards support](https://vconnect.atlassian.net/browse/IE-276) for `bin/dependencies` whitelist.

2.7.0
===========
New features:
* Added DB DDL usage scanner for PHP files: scans for `->getTable()` and `->getTableName()` calls;

Internal framework changes:
* Implemented event observers mechanism;

Fixes:
* [Fix](https://vconnect.atlassian.net/browse/IE-271) for db_schema.xml dependency checker related to disabled foreign keys;

2.6.0
===========
* Added GraphQl schema dependency checker;
* Implemented DI;
* Fixed disassembling whitelist.

2.5.2.
===========
* Added 'explain' argument for disassembling command
* Extension explain will either provide extension replace instruction or provide a reason why extension can not be removed
* Added prefix arguments for disassembling command
* Added 5m cache between command run and option to disable cache during the run

2.5.1
===========
* Fix broken dependency checker
* Update README.md with info regarding disassembling roadmap
* Fix wrong composer.json reading for packages that have more than one composer.json file

2.5.0
===========
* Add Magento 2 disassemble roadmap command
* Command provide list and order of packages that can be replaced in root composer (based on results of dependency analysis)

2.4.0
============
* Add support of queue configs dependency check
* Refactoring

2.3.0
============
* Implement Plugins check
* Refactoring and Bugfixes

2.2.0
=============
* Fixed bug with suggested packages that are already required
* Fixed bug with loading .xml files from different scopes

2.1.0
=============
* Beautified console output, enhanced `--help` command, replaced console interaction logic with `league/climate` library 
* Implemented db_schema.xml dependency checker
* Use `composer/composer` library for parsing `composer.lock` file
* Some minor refactoring

2.0.0
=============
* Implemented the logic of separating dependencies on 'require' and 'suggest';
* Added functionality to search dependencies in di.xml, system.xml, extension_attributes.xml;
* Added error handler to handle error with missed directory in structure checker and dependencies checker;

1.1.1
=============
* Fixed PHP 8.1 Compatibility

