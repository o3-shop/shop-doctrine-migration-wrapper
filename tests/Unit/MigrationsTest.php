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

namespace OxidEsales\DoctrineMigrationWrapper\Tests\Unit;

use OxidEsales\DoctrineMigrationWrapper\DoctrineApplicationBuilder;
use OxidEsales\DoctrineMigrationWrapper\MigrationAvailabilityChecker;
use OxidEsales\DoctrineMigrationWrapper\Migrations;
use OxidEsales\DoctrineMigrationWrapper\MigrationsPathProvider;
use OxidEsales\Facts\Facts;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;

final class MigrationsTest extends TestCase
{
    /**
     * @param null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
    }

    /**
     * Check if Doctrine Application mock is called
     * when migrations are available.
     */
    public function testCallsDoctrineMigrations(): void
    {
        $doctrineApplication = $this->getDoctrineMock(true);

        $doctrineApplicationBuilder = $this->getDoctrineApplicationBuilderStub($doctrineApplication);

        $migrationsPathProvider = $this->getMigrationsPathProviderStub(['edition' => 'path_to_migrations']);

        $pathToDbConfig = '';

        $migrationAvailabilityChecker = $this->getMigrationAvailabilityStub(true);

        $migrations = new Migrations(
            $doctrineApplicationBuilder,
            $pathToDbConfig,
            $migrationAvailabilityChecker,
            $migrationsPathProvider
        );
        $this->assertSame(0, $migrations->execute('migrations:migrate'));
    }

    /**
     * Check if Doctrine Application mock is called with right parameters
     * when migrations are available.
     */
    public function testExecuteCEMigration(): void
    {
        $command = 'migrations:migrate';
        $dbConfigFilePath = 'path_to_DB_config_file';
        $ceMigrationsPath = 'path_to_ce_migrations';

        $input = new ArrayInput([
            '--configuration' => $ceMigrationsPath,
            '--db-configuration' => $dbConfigFilePath,
            '-n' => true,
            'command' => $command
        ]);

        $doctrineApplication = $this->getDoctrineMock(true, $input);

        $doctrineApplicationBuilder = $this->getDoctrineApplicationBuilderStub($doctrineApplication);

        $migrationsPathProvider = $this->getMigrationsPathProviderStub(['ce' => $ceMigrationsPath]);

        $migrationAvailabilityChecker = $this->getMigrationAvailabilityStub(true);

        $migrations = new Migrations(
            $doctrineApplicationBuilder,
            $dbConfigFilePath,
            $migrationAvailabilityChecker,
            $migrationsPathProvider
        );

        $migrations->execute($command);
    }

    /**
     * Tests that all migrations are called what's defined in a Shop facts
     * with an order from Facts.
     */
    public function testExecuteAllMigrations(): void
    {
        $command = 'migrations:migrate';
        $dbConfigFilePath = 'path_to_DB_config_file';
        $ceMigrationsPath = 'path_to_ce_migrations';
        $peMigrationsPath = 'path_to_pe_migrations';
        $eeMigrationsPath = 'path_to_ee_migrations';
        $migrationPaths = [
            'ce' => $ceMigrationsPath,
            'pe' => $peMigrationsPath,
            'ee' => $eeMigrationsPath,
        ];

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

        $doctrineApplication = $this->createPartialMock(Application::class, ['run']);
        $doctrineApplication->expects($this->at(0))->method('run')->with($inputCE);
        $doctrineApplication->expects($this->at(1))->method('run')->with($inputPE);
        $doctrineApplication->expects($this->at(2))->method('run')->with($inputEE);

        $doctrineApplicationBuilder = $this->getDoctrineApplicationBuilderStub($doctrineApplication);

        $migrationsPathProvider = $this->getMigrationsPathProviderStub($migrationPaths);

        $migrationAvailabilityChecker = $this->getMigrationAvailabilityStub(true);

        $migrations = new Migrations(
            $doctrineApplicationBuilder,
            $dbConfigFilePath,
            $migrationAvailabilityChecker,
            $migrationsPathProvider
        );

        $migrations->execute($command);
    }

    /**
     * Tests that only requested migration is called even when more migrations exist.
     * Does testing by calling migration in different case sensitivity.
     */
    public function testExecuteOnlyRequestedMigration(): void
    {
        $command = 'migrations:migrate';
        $dbConfigFilePath = 'path_to_DB_config_file';
        $eeMigrationsPath = 'path_to_ee_migrations';
        $migrationPaths = [
            'eE' => $eeMigrationsPath,
        ];

        $inputEE = new ArrayInput([
            '--configuration' => $eeMigrationsPath,
            '--db-configuration' => $dbConfigFilePath,
            '-n' => true,
            'command' => $command
        ]);

        $doctrineApplication = $this->createPartialMock(Application::class, ['run']);
        $doctrineApplication->expects($this->once())->method('run')->with($inputEE);

        $doctrineApplicationBuilder = $this->getDoctrineApplicationBuilderStub($doctrineApplication);

        $migrationsPathProvider = $this->getMigrationsPathProviderStub($migrationPaths);

        $migrationAvailabilityChecker = $this->getMigrationAvailabilityStub(true);

        $migrations = new Migrations(
            $doctrineApplicationBuilder,
            $dbConfigFilePath,
            $migrationAvailabilityChecker,
            $migrationsPathProvider
        );

        $migrations->execute($command, 'Ee');
    }

    /**
     * Tests that no error appears when no migrations exist for requested edition.
     */
    public function testNoErrorWhenNoMigrationExistForRequestedEdition(): void
    {
        $command = 'migrations:migrate';
        $dbConfigFilePath = 'path_to_DB_config_file';
        $migrationPaths = [
            'ce' => 'path_to_ce_migrations',
            'pe' => 'path_to_pe_migrations',
            'ee' => 'path_to_ee_migrations',
        ];

        $doctrineApplication = $this->createPartialMock(Application::class, ['run']);
        $doctrineApplication->expects($this->never())->method('run');

        $doctrineApplicationBuilder = $this->getDoctrineApplicationBuilderStub($doctrineApplication);

        $migrationsPathProvider = $this->getMigrationsPathProviderStub($migrationPaths);

        $migrationAvailabilityChecker = $this->getMigrationAvailabilityStub(false);

        $migrations = new Migrations(
            $doctrineApplicationBuilder,
            $dbConfigFilePath,
            $migrationAvailabilityChecker,
            $migrationsPathProvider
        );

        $migrations->execute($command, 'PR');
    }

    /**
     * Check if Doctrine Application mock is NOT called
     * when migrations are NOT available.
     */
    public function testSkipMigrationWhenItDoesNotExist(): void
    {
        $command = 'migrations:migrate';
        $dbConfigFilePath = 'path_to_DB_config_file';
        $ceMigrationsPath = 'path_to_ce_migrations';

        $doctrineApplication = $this->getDoctrineMock(false);

        $doctrineApplicationBuilder = $this->getDoctrineApplicationBuilderStub($doctrineApplication);

        $migrationsPathProvider = $this->getMigrationsPathProviderStub(['ce' => $ceMigrationsPath]);

        $migrationAvailabilityChecker = $this->getMigrationAvailabilityStub(false);

        $migrations = new Migrations(
            $doctrineApplicationBuilder,
            $dbConfigFilePath,
            $migrationAvailabilityChecker,
            $migrationsPathProvider
        );

        $migrations->execute($command);
    }

    /**
     * Check if migrations availability checker is called with a right parameter.
     */
    public function testMigrationAvailabilityCheckerCalledWithCorrectPath(): void
    {
        $command = 'migrations:migrate';
        $dbConfigFilePath = 'path_to_DB_config_file';
        $ceMigrationsPath = 'path_to_ce_migrations';

        $doctrineApplication = $this->getDoctrineStub();

        $doctrineApplicationBuilder = $this->getDoctrineApplicationBuilderStub($doctrineApplication);

        $migrationsPathProvider = $this->getMigrationsPathProviderStub(['ce' => $ceMigrationsPath]);

        $migrationAvailabilityChecker = $this->createPartialMock(
            MigrationAvailabilityChecker::class,
            ['migrationExists']
        );
        $migrationAvailabilityChecker->expects($this->atLeastOnce())
            ->method('migrationExists')
            ->with($ceMigrationsPath);

        $migrations = new Migrations(
            $doctrineApplicationBuilder,
            $dbConfigFilePath,
            $migrationAvailabilityChecker,
            $migrationsPathProvider
        );

        $migrations->execute($command);
    }

    /**
     * Check if generates new migration when no migration exist in a folder.
     */
    public function testRunGenerateMigrationCommandEvenIfNoMigrationExist(): void
    {
        $command = 'migrations:generate';
        $dbConfigFilePath = 'path_to_DB_config_file';
        $ceMigrationsPath = 'path_to_ce_migrations';

        $doctrineApplication = $this->getDoctrineMock(true);

        $doctrineApplicationBuilder = $this->getDoctrineApplicationBuilderStub($doctrineApplication);

        $migrationsPathProvider = $this->getMigrationsPathProviderStub(['ce' => $ceMigrationsPath]);

        $migrationAvailabilityChecker = $this->getMigrationAvailabilityStub(false);

        $migrations = new Migrations(
            $doctrineApplicationBuilder,
            $dbConfigFilePath,
            $migrationAvailabilityChecker,
            $migrationsPathProvider
        );

        $migrations->execute($command);
    }

    /**
     * Test to check if error code is passed from Doctrine to upper caller.
     */
    public function testReturnErrorCodeWhenMigrationFail(): void
    {
        $errorCode = 1;

        $doctrineApplication = $this->getDoctrineStub($errorCode);

        $doctrineApplicationBuilder = $this->getDoctrineApplicationBuilderStub($doctrineApplication);

        $migrationsPathProvider = $this->getMigrationsPathProviderStub(['edition' => 'path_to_migrations']);

        $pathToDbConfig = '';

        $migrationAvailabilityChecker = $this->getMigrationAvailabilityStub(true);

        $migrations = new Migrations(
            $doctrineApplicationBuilder,
            $pathToDbConfig,
            $migrationAvailabilityChecker,
            $migrationsPathProvider
        );

        $this->assertSame($errorCode, $migrations->execute('migrations:migrate'));
    }

    public function testExecuteWithEmptyInputWillCallDefaultCommand(): void
    {
        $application = $this->prophesize(Application::class);
        $applicationBuilder = $this->prophesize(DoctrineApplicationBuilder::class);
        $applicationBuilder->build()->willReturn($application);
        $migrationsPathProvider = $this->prophesize(MigrationsPathProvider::class);
        $migrationsPathProvider->getMigrationsPath(null)->willReturn(['something']);
        $checker = $this->prophesize(MigrationAvailabilityChecker::class);

        (new Migrations(
            $applicationBuilder->reveal(),
            '',
            $checker->reveal(),
            $migrationsPathProvider->reveal()
        ))->execute('');

        $application->run(
            Argument::that(
                static function (ArrayInput $input) {
                    return $input->getParameterOption('command') === 'migrations:status';
                }
            ),
            Argument::any()
        )->shouldBeCalledOnce();
    }

    /**
     * @param $runsAtLeastOnce
     * @param null $callWith
     * @return MockObject|Application
     */
    private function getDoctrineMock($runsAtLeastOnce, $callWith = null): MockObject
    {
        $doctrineApplication = $this->createPartialMock(Application::class, ['run']);

        if ($runsAtLeastOnce && is_null($callWith)) {
            $doctrineApplication->expects($this->atLeastOnce())->method('run');
        } elseif ($runsAtLeastOnce) {
            $doctrineApplication->expects($this->atLeastOnce())->method('run')->with($callWith);
        } else {
            $doctrineApplication->expects($this->never())->method('run');
        }

        return $doctrineApplication;
    }

    /**
     * @param int $result
     * @return MockObject|Application
     */
    private function getDoctrineStub($result = null): MockObject
    {
        $doctrineApplication = $this->createPartialMock(Application::class, ['run']);
        $doctrineApplication->method('run')->willReturn($result ? 1 : 0);

        return $doctrineApplication;
    }

    /**
     * @param $doctrineApplication
     * @return MockObject|DoctrineApplicationBuilder
     */
    private function getDoctrineApplicationBuilderStub($doctrineApplication): MockObject
    {
        $doctrineApplicationBuilder = $this->createPartialMock(DoctrineApplicationBuilder::class, ['build']);
        $doctrineApplicationBuilder->method('build')->willReturn($doctrineApplication);

        return $doctrineApplicationBuilder;
    }

    /**
     * @param $migrationPaths
     * @return MockObject|Facts
     */
    private function getMigrationsPathProviderStub($migrationPaths): MockObject
    {
        $migrationsPathProvider = $this->getMockBuilder(MigrationsPathProvider::class)
            ->setMethods(['getMigrationsPath'])
            ->setConstructorArgs([new Facts()])
            ->getMock();

        $migrationsPathProvider->method('getMigrationsPath')->willReturn($migrationPaths);

        return $migrationsPathProvider;
    }

    /**
     * @param $ifMigrationsAvailable
     * @return MockObject
     */
    private function getMigrationAvailabilityStub($ifMigrationsAvailable): MockObject
    {
        $migrationAvailabilityChecker = $this->createPartialMock(
            MigrationAvailabilityChecker::class,
            ['migrationExists']
        );
        $migrationAvailabilityChecker->method('migrationExists')->willReturn($ifMigrationsAvailable);

        return $migrationAvailabilityChecker;
    }
}
