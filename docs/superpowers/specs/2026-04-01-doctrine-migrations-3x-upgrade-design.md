# Design: doctrine/migrations 2.x → 3.x Upgrade

**Date:** 2026-04-01
**Branch:** b-1.6

## Context

This package wraps doctrine/migrations to run database migrations for O3-Shop editions and modules. It currently depends on `doctrine/migrations ^2.3.2` and must be upgraded to `^3.0`, which requires DBAL 3.x.

## Breaking change: `--db-configuration` removed

In migrations 2.x, commands accepted two runtime CLI options:
- `--configuration` — path to the YAML migration config file
- `--db-configuration` — path to a PHP file returning a DBAL connection config array

In migrations 3.x, `--db-configuration` no longer exists. The database connection must be pre-wired into a `DependencyFactory` before the Symfony Console `Application` is built. The `ConsoleRunner::createApplication()` signature changed from accepting a `HelperSet` to accepting a `DependencyFactory`.

## Architecture

`DoctrineApplicationBuilder::build()` gains two parameters — `$dbFilePath` and `$configPath` — and builds the full `DependencyFactory` internally:

```
DependencyFactory::fromConnection(
    new YamlFile($configPath),      // migration config from YAML
    new ExistingConnection($conn)   // DBAL connection from db config array
)
```

This is called once per edition in `Migrations::execute()`, where both paths are already available.

Because configuration is now embedded in the `DependencyFactory`, the `ArrayInput` passed to `Application::run()` no longer needs `--configuration` or `--db-configuration`.

## YAML configuration format change

Migrations 3.x uses a different YAML schema. The keys `migrations_namespace`, `migrations_directory`, and `table_name` are replaced:

| 2.x | 3.x |
|-----|-----|
| `migrations_namespace: Foo\Bar` + `migrations_directory: data` | `migrations_paths: { 'Foo\Bar': data }` |
| `table_name: oxmigrations_ce` | `table_storage: { table_name: oxmigrations_ce }` |

## File-by-file changes

### `composer.json`
- `"doctrine/migrations": "^2.3.2"` → `"^3.0"`
- Add `"doctrine/dbal": "^3.9"` (explicit pin since we now create DBAL connections directly)

### `src/DoctrineApplicationBuilder.php`
- Change signature: `build()` → `build(string $dbFilePath, string $configPath): Application`
- Replace `new HelperSet()` + `ConsoleRunner::createApplication($helperSet)` with:
  1. `$dbConfig = include $dbFilePath;`
  2. `$conn = DriverManager::getConnection($dbConfig);`
  3. `$factory = DependencyFactory::fromConnection(new YamlFile($configPath), new ExistingConnection($conn));`
  4. `ConsoleRunner::createApplication($factory)`
- Add imports: `Doctrine\DBAL\DriverManager`, `Doctrine\Migrations\Configuration\Connection\ExistingConnection`, `Doctrine\Migrations\Configuration\Migration\YamlFile`, `Doctrine\Migrations\DependencyFactory`
- Remove import: `Symfony\Component\Console\Helper\HelperSet`

### `src/Migrations.php`
- `execute()`: `$this->doctrineApplicationBuilder->build()` → `$this->doctrineApplicationBuilder->build($this->dbFilePath, $migrationPath)`
- `formDoctrineInput()`: remove `'--configuration'` and `'--db-configuration'` from the `ArrayInput` array; remove the now-unused `$dbFilePath` parameter

### `tests/testData/source/migration/migrations.yml`
```yaml
name: Oxid Migrations CE
migrations_paths:
  'OxidEsales\DoctrineMigrationWrapper\source\Migrations': data
table_storage:
  table_name: oxmigrations_ce
```

### `tests/testData/source/migration/project_migrations.yml`
```yaml
name: Oxid Migrations (Project)
migrations_paths:
  'OxidEsales\DoctrineMigrationWrapper\source\Migrations': project_data
table_storage:
  table_name: oxmigrations_project
```

### `tests/Unit/MigrationsTest.php`
- All `ArrayInput` literals in assertions must drop `'--configuration'` and `'--db-configuration'` keys
- Expected input is now just `['-n' => true, 'command' => $command]`
- `build()` mock stubs continue to work (PHPUnit partial mocks don't enforce parameter types) but callers should reflect the new two-argument signature

## No-change files

| File | Reason |
|------|--------|
| `src/MigrationAvailabilityChecker.php` | No doctrine API usage |
| `src/MigrationsBuilder.php` | Just constructs objects; no doctrine API |
| `src/MigrationsPathProvider.php` | No doctrine API usage |
| `src/migrations-db.php` | Returns array; DBAL 3.x accepts same format |
| `tests/testData/.../Version*.php` | `AbstractMigration` + `addSql()` unchanged in 3.x |
| `tests/Unit/MigrationAvailabilityCheckerTest.php` | No doctrine API |
| `tests/Integration/MigrationsTest.php` | No doctrine API |