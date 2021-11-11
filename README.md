## Integrity checker

Package allows to run static analysis on Magento 2 Module Packages to provide an integrity check of package.

### Supported tools: ###

- **Composer.json package dependencies checker** - check *.php and *.phtml on a subject if other packages used inside
  and check if corresponding module/package is declared as required in composer.json.
- **Module.xml dependencies checker** - analyse if packages' etc/module.xml file contains in 'sequence' section all
  magneto 2 modules which classes are used in *.php and *.phtml files of the package.
- **Package structure checker** - verify if all newly added Magneto 2 modules has a proper structure with all required
  files.

### Standalone Installation ###
1. Install project from Vconnect satis
```bash
composer create-project vconnect/integrity-checker --repository-url="{\"type\": \"composer\", \"url\": \"https://composer.vconnect.systems\"}" integrity-checker
```

### Package Installation ###
1. Add Vconnect repository to list of available repositories for your project composer.json
```bash
composer config repositories.integrity-checker '{"type": "composer", "url": "https://composer.vconnect.systems"}'
```
2. Install package via composer
```bash
composer require --dev vconnect/integrity-checker
```

### Usage ###

#### Dependencies Checker ####

```bash
bin/dependencies {magento root} {folder} {folder2} {folder3}
```

{magento root} - path to Magento 2 project root directory.
Tool require composer.lock to be defined.
All packages inside {folder}'s will be recognized by composer.json file. {folder} - expected to be relative inside the
magento root folder. Dependencies check will be run for composer.json and etc/module.xml together.

#### Module Structure Checker ####

```bash
bin/structure {magento root} {folder} {folder2} {folder3}
```

{magento root} - path to Magento 2 project root directory.
Tool collects all packages in {folder} by registration.php files. For each module it compares
current structure with Standard structure and print diff, if Standard structure was not followed.

Standard package structure:

```bash
docs
src
  etc
    module.xml
README.md
composer.json
registration.php
```
