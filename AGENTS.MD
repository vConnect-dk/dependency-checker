# Agent's Guide to Integrity Checker

This document provides essential information for AI agents and developers working on the `integrity-checker` repository.

## Project Overview

`vconnect/integrity-checker` is a static analysis tool for Magento 2 module packages. It ensures package integrity by checking dependencies in `composer.json` and `module.xml`.

## Core Functionality

1.  **Dependencies Checker (`bin/dependencies`)**: Checks if all used classes/modules in PHP, PHTML, and XML files are declared in `composer.json` and `etc/module.xml`.
2.  **Disassembling Roadmap (`bin/disassembling`)**: Analyzes module dependencies to provide a roadmap for disassembling/removing modules while maintaining a functional store.

## Project Structure

- `bin/`: Executable scripts (entry points).
  - `dependencies`: Dependency analysis tool.
  - `disassembling`: Roadmap generation tool.
- `src/`: Source code (PSR-4 namespace `Vconnect\IntegrityChecker\`).
  - `Analysis/`: Core analysis logic, services, and data models.
  - `Application/`: Application-level logic and DI configuration.
  - `Domain/`: Domain-specific models and logic (e.g., `PackagesRegistry`).
  - `Utils/`: Helper classes.
- `tests/`: Test suite.
  - `testsuite/`: PHPUnit tests.
  - `sandbox/`: Sample Magento structures for testing.
- `vendor/`: Composer dependencies.

## Technical Stack

- **PHP**: `~8.2.0 | ~8.3.0 | ~8.4.0 | ~8.5.0`
- **Dependencies**:
  - `league/climate`: CLI output formatting.
  - `composer/composer`: Working with composer files and repositories.
  - `php-di/php-di`: Dependency Injection.
  - `webonyx/graphql-php`: GraphQL support.
- **Testing**: PHPUnit.

## Key Development Commands

### Installation
```bash
composer install
```

### Running Tests
```bash
vendor/bin/phpunit -c tests/phpunit.xml
```

### Running Tools (Development)
You can run the tools directly from the `bin` directory:
```bash
php bin/dependencies {magento_root} {folder}
php bin/disassembling {magento_root} {whitelist_or_file}
```

## Implementation Details

- **Dependency Injection**: The project uses `php-di`. Check `src/Application.php` or `src/etc/` for container configuration.
- **Analysis Logic**: Most checkers implement `Vconnect\IntegrityChecker\Analysis\Service\AnalyzerInterface`.
- **Magento Integration**: The tool interacts with Magento projects by reading their `composer.json`, `composer.lock`, and `etc/module.xml` files.

## Adding New Features

1.  **New Checker**: Implement `AnalyzerInterface` in `src/Analysis/Service/`, register it in the DI container if necessary, and create a new bin script or integrate it into an existing one.
2.  **Testing**: Add new test cases to `tests/testsuite/`. Use `tests/sandbox/` to create mock Magento module structures for integration tests.
