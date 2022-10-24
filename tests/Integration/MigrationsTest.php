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

namespace OxidEsales\DoctrineMigrationWrapper\Tests\Integration;

use OxidEsales\DoctrineMigrationWrapper\MigrationsBuilder;
use OxidEsales\Facts\Config\ConfigFile;
use PHPUnit\Framework\TestCase;

final class MigrationsTest extends TestCase
{
    /** @var ConfigFile */
    private $configFile;

    /** @var EnvironmentPreparator */
    private $environmentPreparator;

    public function __construct()
    {
        $this->environmentPreparator = new EnvironmentPreparator();
        parent::__construct();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->environmentPreparator->setupEnvironment();
        $this->configFile = new ConfigFile();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->environmentPreparator->cleanEnvironment();
    }

    /**
     * Run migration for one edition and one project to test that they works.
     * Tests that:
     * - integration with Doctrine Migration actually works
     * - it is possible to run two migrations in a row
     * - Migration Builder actually works
     */
    public function testMigrateSuccess(): void
    {
        $migration = (new MigrationsBuilder())->build();
        $migration->execute('migrations:migrate');

        $databaseName = $this->configFile->dbName;
        $databaseConnection = new \PDO(
            'mysql:host=' . $this->configFile->dbHost,
            $this->configFile->dbUser,
            $this->configFile->dbPwd
        );

        $result = $databaseConnection->query(
            "SELECT id as entries FROM `$databaseName`.`test_doctrine_migration_wrapper`"
        );
        $this->assertSame(2, $result->rowCount(), 'There must be one row for shop migration and one for project.');

        $result = $databaseConnection->query(
            "SELECT 1 FROM `$databaseName`.`test_doctrine_migration_wrapper` WHERE id = 'shop_migration'"
        );
        $this->assertSame(1, $result->rowCount(), 'There must be one row for shop migration');

        $result = $databaseConnection->query(
            "SELECT 1 FROM `$databaseName`.`test_doctrine_migration_wrapper` WHERE id = 'project_migration'"
        );
        $this->assertSame(1, $result->rowCount(), 'There must be one row for project migration');
    }
}
