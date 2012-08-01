Installation
============

First install the Sonata Admin Bundle which provides Core functionalities. The ``EntityAudit`` is an optional
history feature (https://github.com/simplethings/EntityAudit).

Download bundles
----------------

Use composer ::

    php composer.phar require sonata-project/doctrine-orm-admin-bundle

    # optional bundle
    php composer.phar require simplethings/entity-audit-bundle


Configuration
-------------

Next, be sure to enable the bundles in your autoload.php and AppKernel.php
files:

.. code-block:: php

    <?php
    // app/AppKernel.php
    public function registerBundles()
    {
        return array(
            // ...
            new Sonata\DoctrineORMAdminBundle\SonataDoctrineORMAdminBundle(),
            new SimpleThings\EntityAudit\SimpleThingsEntityAuditBundle(),
            // ...
        );
    }
