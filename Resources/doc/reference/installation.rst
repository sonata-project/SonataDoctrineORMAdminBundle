Installation
============

First install the SonataAdminBundle which provides Core functionalities.
Follow `these <http://sonata-project.org/bundles/admin/master/doc/reference/installation.html>`_ instructions to do so.

Download the bundle
-------------------

Use composer:

.. code-block:: bash

    php composer.phar require sonata-project/doctrine-orm-admin-bundle

You'll be asked to type in a version constraint. 'dev-master' will usually get you the latest
version. Check `packagist <https://packagist.org/packages/sonata-project/doctrine-orm-admin-bundle>`_
for older versions:

.. code-block:: bash

    Please provide a version constraint for the sonata-project/doctrine-orm-admin-bundle requirement: dev-master


Enable the bundle
-----------------

Next, be sure to enable the bundle in your AppKernel.php file:

.. code-block:: php

    <?php
    // app/AppKernel.php
    public function registerBundles()
    {
        return array(
            // ...
            new Sonata\DoctrineORMAdminBundle\SonataDoctrineORMAdminBundle(),
            // ...
        );
    }
