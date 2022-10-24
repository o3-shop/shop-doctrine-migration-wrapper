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

use OxidEsales\EshopCommunity\Internal\Container\BootstrapContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ProjectConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;
use OxidEsales\Facts\Facts;
use Webmozart\PathUtil\Path;

class MigrationsPathProvider implements MigrationsPathProviderInterface
{
    /**
     * @var Facts
     */
    private $facts;

    /**
     * @param Facts $facts
     */
    public function __construct(Facts $facts)
    {
        $this->facts = $facts;
    }

    /**
     * @param null $edition
     *
     * @return array
     */
    public function getMigrationsPath($edition = null): array
    {
        $allMigrationPaths = array_merge($this->getShopEditionsPath(), $this->getModulesPath());

        if (is_null($edition)) {
            return $allMigrationPaths;
        }

        $migrationPaths = [];
        foreach ($allMigrationPaths as $migrationEdition => $migrationPath) {
            if (strtolower($migrationEdition) === strtolower($edition)) {
                $migrationPaths[$migrationEdition] = $migrationPath;
                break;
            }
        }

        return $migrationPaths;
    }

    /**
     * @return array
     */
    private function getShopEditionsPath(): array
    {
        return $this->facts->getMigrationPaths();
    }

    /**
     * @return array
     */
    private function getModulesPath(): array
    {
        $moduleMigrationPaths = [];

        $bootstrapContainer = BootstrapContainerFactory::getBootstrapContainer();

        $projectConfigurationDao = $bootstrapContainer
            ->get(ProjectConfigurationDaoInterface::class);

        $basicContext = $bootstrapContainer
            ->get(BasicContextInterface::class);

        $shopConfigurationDao = $projectConfigurationDao
            ->getConfiguration()
            ->getShopConfiguration($basicContext->getDefaultShopId());

        foreach ($shopConfigurationDao->getModuleConfigurations() as $moduleConfiguration) {
            $migrationConfigurationPath = Path::join(
                $basicContext->getModulesPath(),
                $moduleConfiguration->getPath(),
                '/migration/migrations.yml'
            );
            if (file_exists($migrationConfigurationPath)) {
                $moduleMigrationPaths[$moduleConfiguration->getId()] = $migrationConfigurationPath;
            }
        }

        return $moduleMigrationPaths;
    }
}
