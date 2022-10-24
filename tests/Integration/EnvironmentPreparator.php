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

use OxidEsales\Facts\Config\ConfigFile;
use PDO;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Setup database and file system for integration test.
 */
final class EnvironmentPreparator
{
    /** @var ConfigFile */
    private $configFile;
    /** @var PDO */
    private $databaseConnection;

    public function setupEnvironment(): void
    {
        $this->copySystemFiles();
        $this->configFile = new ConfigFile();
        $this->openDatabaseConnection();
        $this->setUpDatabase();
    }

    public function cleanEnvironment(): void
    {
        $this->destroyDatabase();
        $this->closeDatabaseConnection();
        $this->deleteSystemFiles();
    }

    private function openDatabaseConnection(): void
    {
        $this->databaseConnection = new PDO(
            "mysql:host={$this->configFile->dbHost}",
            $this->configFile->dbUser,
            $this->configFile->dbPwd
        );
    }

    private function setUpDatabase(): void
    {
        $databaseName = $this->configFile->dbName;
        $this->databaseConnection->exec("CREATE DATABASE `$databaseName`");
    }

    private function copySystemFiles(): void
    {
        $fileSystem = new Filesystem();
        $pathFromTestData = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'testData']);
        $pathToTestData = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..']);
        $fileSystem->mirror($pathFromTestData, $pathToTestData);
    }

    private function destroyDatabase(): void
    {
        $databaseName = $this->configFile->dbName;
        $this->databaseConnection->exec("DROP DATABASE `$databaseName`");
    }

    private function deleteSystemFiles(): void
    {
        $fileSystem = new Filesystem();
        $pathToSourceTestData = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'source']);
        $pathToVarTestData = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'var']);
        $fileSystem->remove($pathToSourceTestData);
        $fileSystem->remove($pathToVarTestData);
    }

    private function closeDatabaseConnection(): void
    {
        $this->databaseConnection = null;
    }
}
