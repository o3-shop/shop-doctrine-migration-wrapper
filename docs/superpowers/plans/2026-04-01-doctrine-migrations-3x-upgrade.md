# doctrine/migrations 3.x Upgrade Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Upgrade `doctrine/migrations` from `^2.3.2` to `^3.0` (and pin `doctrine/dbal ^3.9`), fixing all 2.x API usage in PHP source and YAML test data.

**Architecture:** `DoctrineApplicationBuilder::build()` gains two parameters (`$dbFilePath`, `$configPath`) and internally builds a `DependencyFactory` from a DBAL connection + YAML loader, replacing the removed `--db-configuration` CLI option. `Migrations::execute()` passes both paths to `build()`, and `formDoctrineInput()` is simplified to drop the two config args that are now irrelevant.

**Tech Stack:** PHP 7.4+/8.x, doctrine/migrations ^3.0, doctrine/dbal ^3.9, PHPUnit 8.5, Symfony Console

---

### Task 1: Update composer.json version constraints

**Files:**
- Modify: `composer.json`

- [ ] **Step 1: Edit composer.json**

Change:
```json
"doctrine/migrations": "^2.3.2",
```
To:
```json
"doctrine/dbal": "^3.9",
"doctrine/migrations": "^3.0",
```

The full `require` block becomes:
```json
"require": {
    "php": "^7.4 || ^8.0",
    "doctrine/dbal": "^3.9",
    "doctrine/migrations": "^3.0",
    "composer/package-versions-deprecated": "^1",
    "o3-shop/shop-facts": "^1.0.0",
    "symfony/console": "*"
},
```

- [ ] **Step 2: Verify composer resolves the new constraints**

```bash
composer update doctrine/dbal doctrine/migrations --dry-run
```

Expected: no conflict errors. The dry-run will list packages to install/update. If you see `Your requirements could not be resolved`, check the PHP version constraint or other locked packages.

- [ ] **Step 3: Commit**

```bash
git add composer.json
git commit -m "chore: bump doctrine/migrations to ^3.0 and pin doctrine/dbal ^3.9"
```

---

### Task 2: Update YAML test data to migrations 3.x format

**Files:**
- Modify: `tests/testData/source/migration/migrations.yml`
- Modify: `tests/testData/source/migration/project_migrations.yml`

In migrations 3.x, `migrations_namespace` + `migrations_directory` are replaced by `migrations_paths` (a `namespace: directory` map), and `table_name` moves under `table_storage`.

- [ ] **Step 1: Rewrite migrations.yml**

Replace the entire file content with:
```yaml
name: Oxid Migrations CE
migrations_paths:
  'OxidEsales\DoctrineMigrationWrapper\source\Migrations': data
table_storage:
  table_name: oxmigrations_ce
```

- [ ] **Step 2: Rewrite project_migrations.yml**

Replace the entire file content with:
```yaml
name: Oxid Migrations (Project)
migrations_paths:
  'OxidEsales\DoctrineMigrationWrapper\source\Migrations': project_data
table_storage:
  table_name: oxmigrations_project
```

- [ ] **Step 3: Commit**

```bash
git add tests/testData/source/migration/migrations.yml \
        tests/testData/source/migration/project_migrations.yml
git commit -m "chore: update test YAML configs to doctrine/migrations 3.x format"
```

---

### Task 3: Update unit tests to expect new ArrayInput (TDD — write failing tests first)

The observable contract change: `Application::run()` will no longer receive `--configuration` or `--db-configuration` in its input, because those are now handled inside `DoctrineApplicationBuilder::build()`. Update the test expectations first; they will fail until Task 5 is complete.

**Files:**
- Modify: `tests/Unit/MigrationsTest.php`

- [ ] **Step 1: Update testExecuteCEMigration**

Find the `$input` construction in `testExecuteCEMigration` (around line 84):

```php
$input = new ArrayInput([
    '--configuration' => $ceMigrationsPath,
    '--db-configuration' => $dbConfigFilePath,
    '-n' => true,
    'command' => $command
]);
```

Replace with:
```php
$input = new ArrayInput([
    '-n' => true,
    'command' => $command
]);
```

- [ ] **Step 2: Update testExecuteAllMigrations**

Find the three `ArrayInput` constructions in `testExecuteAllMigrations` (around lines 126–145):

```php
$inputCE = new ArrayInput([
    '--configuration' => $ceMigrationsPath,
    '--db-configuration' => $dbConfigFilePath,
    '-n' => true,
    'command' => $command
]);

$inputPE = new ArrayInput([
    '--configuration' => $peMigrationsPath,
    '--db-configuration' => $dbConfigFilePath,
    '-n' => true,
    'command' => $command
]);

$inputEE = new ArrayInput([
    '--configuration' => $eeMigrationsPath,
    '--db-configuration' => $dbConfigFilePath,
    '-n' => true,
    'command' => $command
]);
```

Replace all three with:
```php
$inputCE = new ArrayInput([
    '-n' => true,
    'command' => $command
]);

$inputPE = new ArrayInput([
    '-n' => true,
    'command' => $command
]);

$inputEE = new ArrayInput([
    '-n' => true,
    'command' => $command
]);
```

- [ ] **Step 3: Update testExecuteOnlyRequestedMigration**

Find the `$inputEE` construction in `testExecuteOnlyRequestedMigration` (around line 181):

```php
$inputEE = new ArrayInput([
    '--configuration' => $eeMigrationsPath,
    '--db-configuration' => $dbConfigFilePath,
    '-n' => true,
    'command' => $command
]);
```

Replace with:
```php
$inputEE = new ArrayInput([
    '-n' => true,
    'command' => $command
]);
```

- [ ] **Step 4: Run tests and confirm they FAIL**

```bash
./vendor/bin/phpunit tests/Unit/MigrationsTest.php --testdox
```

Expected: failures in `testExecuteCEMigration`, `testExecuteAllMigrations`, `testExecuteOnlyRequestedMigration` because the implementation still produces the old input shape. Other tests should still pass.

- [ ] **Step 5: Commit the failing tests**

```bash
git add tests/Unit/MigrationsTest.php
git commit -m "test: update ArrayInput expectations for migrations 3.x (failing)"
```

---

### Task 4: Rewrite DoctrineApplicationBuilder for migrations 3.x

**Files:**
- Modify: `src/DoctrineApplicationBuilder.php`

- [ ] **Step 1: Replace the entire file content**

```php
<?php

/**
 * This file is part of O3-Shop.
 *
 * O3-Shop is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3.
 *
 * O3-Shop is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with O3-Shop.  If not, see <http://www.gnu.org/licenses/>
 *
 * @copyright  Copyright (c) 2022 OXID eSales AG (https://www.oxid-esales.com)
 * @copyright  Copyright (c) 2022 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

declare(strict_types=1);

namespace OxidEsales\DoctrineMigrationWrapper;

use Doctrine\DBAL\DriverManager;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use Doctrine\Migrations\Configuration\Migration\YamlFile;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Tools\Console\ConsoleRunner;
use Symfony\Component\Console\Application;

class DoctrineApplicationBuilder
{
    /**
     * Build a fresh Doctrine Migrations console application for the given
     * migration config and database config files.
     *
     * A new application is built for each migration set because each has its
     * own configuration (namespace, table, directory). Reusing an application
     * across sets would carry over stale configuration.
     *
     * @param string $dbFilePath  Path to a PHP file that returns a DBAL connection params array.
     * @param string $configPath  Path to a migrations 3.x YAML configuration file.
     *
     * @return Application
     */
    public function build(string $dbFilePath, string $configPath): Application
    {
        $dbConfig = include $dbFilePath;
        $conn = DriverManager::getConnection($dbConfig);

        $dependencyFactory = DependencyFactory::fromConnection(
            new YamlFile($configPath),
            new ExistingConnection($conn)
        );

        $doctrineApplication = ConsoleRunner::createApplication($dependencyFactory);
        $doctrineApplication->setAutoExit(false);
        $doctrineApplication->setCatchExceptions(false);

        return $doctrineApplication;
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add src/DoctrineApplicationBuilder.php
git commit -m "feat: rewrite DoctrineApplicationBuilder for migrations 3.x DependencyFactory API"
```

---

### Task 5: Update Migrations.php — pass config paths to build(), simplify formDoctrineInput()

**Files:**
- Modify: `src/Migrations.php`

- [ ] **Step 1: Update the build() call in execute()**

Find in `execute()` (around line 99–101):
```php
$doctrineApplication = $this->doctrineApplicationBuilder->build();

$input = $this->formDoctrineInput($command, $migrationPath, $this->dbFilePath);
```

Replace with:
```php
$doctrineApplication = $this->doctrineApplicationBuilder->build($this->dbFilePath, $migrationPath);

$input = $this->formDoctrineInput($command);
```

- [ ] **Step 2: Simplify formDoctrineInput()**

Find the entire `formDoctrineInput` method (around lines 145–153):
```php
private function formDoctrineInput($command, $migrationPath, $dbFilePath): ArrayInput
{
    return new ArrayInput([
        '--configuration' => $migrationPath,
        '--db-configuration' => $dbFilePath,
        '-n' => true,
        'command' => !empty($command) ? $command : self::STATUS_COMMAND,
    ]);
}
```

Replace with:
```php
private function formDoctrineInput($command): ArrayInput
{
    return new ArrayInput([
        '-n' => true,
        'command' => !empty($command) ? $command : self::STATUS_COMMAND,
    ]);
}
```

- [ ] **Step 3: Run the unit tests and confirm they all pass**

```bash
./vendor/bin/phpunit tests/Unit/ --testdox
```

Expected: all tests in `tests/Unit/MigrationsTest.php` and `tests/Unit/MigrationAvailabilityCheckerTest.php` PASS.

- [ ] **Step 4: Commit**

```bash
git add src/Migrations.php
git commit -m "feat: update Migrations to pass config paths to build() and drop obsolete CLI args"
```

---

### Task 6: Final verification

- [ ] **Step 1: Run the full unit test suite**

```bash
./vendor/bin/phpunit tests/Unit/ --testdox
```

Expected output: all tests green, no failures or errors.

- [ ] **Step 2: Verify no remaining 2.x API references**

```bash
grep -r "db-configuration\|migrations_namespace\|migrations_directory\|HelperSet\|ConsoleRunner::createApplication" src/ tests/
```

Expected: no matches.

- [ ] **Step 3: Check for any remaining 2.x config keys in YAML files**

```bash
grep -r "table_name\|migrations_namespace\|migrations_directory" tests/testData/
```

Expected: no matches (all migrated to `table_storage:` and `migrations_paths:`).