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