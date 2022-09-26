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

