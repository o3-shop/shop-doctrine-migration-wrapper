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

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\Output;

/**
 * Class to run Doctrine Migration commands.
 * O3-Shop might have several migrations to run for different edition and project.
 * This class ensures that all needed migrations run.
 */
class Migrations
{
    /** @var  DoctrineApplicationBuilder $doctrineApplicationBuilder */
    private $doctrineApplicationBuilder;

    /** @var  \OxidEsales\DoctrineMigrationWrapper\$MigrationAvailabilityChecker */
    private $migrationAvailabilityChecker;

    /** @var string path to file which contains database configuration for Doctrine Migrations */
    private $dbFilePath;

    /** Command for doctrine to run database migrations. */
    const MIGRATE_COMMAND = 'migrations:migrate';

    private const STATUS_COMMAND = 'migrations:status';

    /** @var Output Add a possibility to provide a custom output handler */
    private $output;

    /**
     * @var MigrationsPathProvider
     */
    private $migrationsPathProvider;

    /**
     *
     * @param $doctrineApplicationBuilder
     * @param $dbFilePath
     * @param $migrationAvailabilityChecker
     * @param $migrationsPathProvider
     */
    public function __construct(
        $doctrineApplicationBuilder,
        $dbFilePath,
        $migrationAvailabilityChecker,
        $migrationsPathProvider
    ) {
        $this->doctrineApplicationBuilder = $doctrineApplicationBuilder;
        $this->dbFilePath = $dbFilePath;
        $this->migrationAvailabilityChecker = $migrationAvailabilityChecker;
        $this->migrationsPathProvider = $migrationsPathProvider;
    }

    /**
     * @param Output $output Add a possibility to provide a custom output handler
     */
    public function setOutput(Output $output = null)
    {
        $this->output = $output;
    }

    /**
     * Execute Doctrine Migration command for all needed Shop edition and project.
     * If Doctrine returns an error code breaks and return it.
     *
     * @param string $command Doctrine Migration command to run.
     * @param string $edition Possibility to run migration only against one edition.
     *
     * @return int error code if one exist or 0 for success
     */
    public function execute($command, $edition = null)
    {
        $migrationPaths = $this->migrationsPathProvider->getMigrationsPath($edition);

        foreach ($migrationPaths as $migrationEdition => $migrationPath) {
            $doctrineApplication = $this->doctrineApplicationBuilder->build();

            $input = $this->formDoctrineInput($command, $migrationPath, $this->dbFilePath);

            if ($this->shouldRunCommand($command, $migrationPath)) {
                $errorCode = $doctrineApplication->run($input, $this->output);
                if ($errorCode) {
                    return $errorCode;
                }
            }
        }

        return 0;
    }

    /**
     * Form input which is expected by Doctrine.
     *
     * @param string $command command to run.
     * @param string $migrationPath path to migration configuration file.
     * @param string $dbFilePath path to database configuration file.
     *
     * @return ArrayInput
     */
    private function formDoctrineInput($command, $migrationPath, $dbFilePath): ArrayInput
    {
        return new ArrayInput([
            '--configuration' => $migrationPath,
            '--db-configuration' => $dbFilePath,
            '-n' => true,
            'command' => !empty($command) ? $command : self::STATUS_COMMAND,
        ]);
    }

    /**
     * Check if command should be performed:
     * - All commands should be performed without additional check except migrate
     * - Migrate command should be performed only if actual migrations exist.
     *
     * @param string $command command to run.
     * @param string $migrationPath path to migration configuration file.
     *
     * @return bool
     */
    private function shouldRunCommand($command, $migrationPath)
    {
        return ($command !== self::MIGRATE_COMMAND
            || $this->migrationAvailabilityChecker->migrationExists($migrationPath));
    }
}
