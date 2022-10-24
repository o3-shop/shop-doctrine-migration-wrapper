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

use Doctrine\Migrations\Tools\Console\ConsoleRunner;
use Symfony\Component\Console\Application;

class DoctrineApplicationBuilder
{
    /**
     * Return new application for each build.
     * Application has a reference to command which has internal cache.
     * Reusing same application object with same command leads to an errors due to an old configuration.
     * For example first run with a CE migrations
     * second run with PE migrations
     * both runs would take path to CE migrations.
     *
     * @return Application
     */
    public function build()
    {
        $helperSet = new \Symfony\Component\Console\Helper\HelperSet();
        $doctrineApplication = ConsoleRunner::createApplication($helperSet);
        $doctrineApplication->setAutoExit(false);
        $doctrineApplication->setCatchExceptions(false); // we handle the exception on our own!

        return $doctrineApplication;
    }
}