O3-Shop doctrine migration integration
=========================================

.. image:: https://travis-ci.org/o3-shop/o3-shop-doctrine-migration-wrapper.svg?branch=master
    :target: https://travis-ci.org/o3-shop/o3-shop-doctrine-migration-wrapper

Document: https://docs.o3-shop.com/developer/en/6.2/development/modules_components_themes/module/database_migration/index.html

Branch Compatibility
--------------------

* master branch is compatible with O3-Shop compilation master
* b-6.4.x branch is compatible with O3-Shop compilation 6.4.x
* b-6.3.x branch is compatible with O3-Shop compilation 6.3.x
* b-3.x branch is compatible with O3-Shop compilation 6.2.x
* b-1.x branch is compatible with O3-Shop compilations before 6.2.x

Description
-----------

O3-Shop uses database migrations for:

- eShop editions migration
- Project specific migrations
- Modules migrations

At the moment O3-Shop uses "Doctrine 2 Migrations" and it's integrated via O3-Shop migration components.

Doctrine Migrations runs migrations with a single configuration. But there was a need to run migration for one or all the
projects and modules (CE, PR and a specific module). For this reason `Doctrine Migration Wrapper` was created.

Running migrations - CLI
------------------------

Script to run migrations is installed within composer bin directory. It accepts two parameters:

- Doctrine Command
- Suite Type (CE, PR or a specific module_id)

.. code:: bash

   vendor/bin/oe-eshop-db_migrate <Doctrine_Command> <Suite_Type>

To get comprehensive information about Doctrine 2 Migrations and available commands as well, please see `official documentation <https://www.doctrine-project.org/projects/doctrine-migrations/en/2.2/index.html>`__.

Example:

.. code:: bash

   vendor/bin/oe-eshop-db_migrate migrations:migrate

This command will run all the migrations which are in O3-Shop specific directories. For example if you have
migration tool will run migrations in this order:

* Community Edition migrations (executed always)
* Project specific migrations (executed always)
* Module migrations (executed when eShop has at least one module with migration)

.. _suite_types:

Suite Types (Generate migration for a single suite)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

It is also possible to run migrations for specific suite by defining `<Suite_Type>` parameter in the command.
This variable defines what type of migration it is. There are 5 suite types:

* **PR** - For project specific migrations. It should be always used for project development.
* **CE** - Generates migration file for O3-Shop Community Edition. It's used for product development only.
* **<module_id>** - Generates migration file for O3-Shop specific module. It’s used for module development only.

Example 1:

.. code:: bash

   vendor/bin/oe-eshop-db_migrate migrations:generate

This command generates migration versions for all the suite types.

Example 2:

.. code:: bash

   vendor/bin/oe-eshop-db_migrate migrations:generate CE

In this case it will be generated only for Community Edition in `vendor/o3-shop/shop_ce/migration` directory.

Use Migrations Wrapper without CLI
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Doctrine Migration Wrapper is written in PHP and also could be used without command line interface. To do so:

- Create ``Migrations`` object with ``MigrationsBuilder->build()``
- Call ``execute`` method with needed parameters


Bugs and Issues
---------------

If you experience any bugs or issues, please report them in the section **O3-Shop (all versions)** of https://bugs.o3-shop.com.
