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

