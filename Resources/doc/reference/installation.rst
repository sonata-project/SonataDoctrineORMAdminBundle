Installation
============

First install the Sonata Admin Bundle which provides Core functionalities. The ``EntityAudit`` is an optional
history feature (https://github.com/simplethings/EntityAudit).

Download bundles
----------------

Add the following lines to the file ``deps``::

    [SonataDoctrineORMAdminBundle]
        git=http://github.com/sonata-project/SonataDoctrineORMAdminBundle.git
        target=/bundles/Sonata/DoctrineORMAdminBundle
        version=origin/2.0

    # optional bundle
    [EntityAudit]
        git=git://github.com/simplethings/EntityAudit.git
        target=/bundles/SimpleThings/EntityAudit

and run::

  bin/vendors install

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

    // autoload.php
    $loader->registerNamespaces(array(
        // ...
        'SimpleThings'      => __DIR__.'/../vendor/bundles',
        // ...
    ));