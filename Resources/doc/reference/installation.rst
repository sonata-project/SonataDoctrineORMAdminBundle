.. index::
    double: Reference; Installation

Installation
============

``SonataDoctrineORMAdminBundle`` is part of a set of bundles aimed at abstracting storage connectivity for ``SonataAdminBundle``.
As such, ``SonataDoctrineORMAdminBundle`` depends on ``SonataAdminBundle`` and will not work without it.

.. note::
    These installation instructions are meant to be used only as part of SonataAdminBundle's installation process, which is documented `here <https://sonata-project.org/bundles/admin/master/doc/reference/installation.html>`_.

Download the bundle
-------------------

Use `Composer`:

.. code-block:: bash

    php composer.phar require sonata-project/doctrine-orm-admin-bundle

You'll be asked to type in a version constraint. `dev-master` will usually get you the latest, bleeding edge version.
Check `packagist <https://packagist.org/packages/sonata-project/doctrine-orm-admin-bundle>`_ for stable and legacy versions:

.. note::

    Please provide a version constraint for the sonata-project/doctrine-orm-admin-bundle requirement: dev-master

Enable the bundle
-----------------

Next, be sure to enable the bundle in your `AppKernel.php` file:

.. code-block:: php

    <?php

    // app/AppKernel.php

    public function registerBundles()
    {
        return array(
            // ...
            // set up basic sonata requirements
            // ...
            new Sonata\DoctrineORMAdminBundle\SonataDoctrineORMAdminBundle(),
            // ...
        );
    }

.. note::

    Don't forget that, as part of `SonataAdminBundle's installation instructions <https://sonata-project.org/bundles/admin/master/doc/reference/installation.html>`_,
    you need to enable additional bundles on `AppKernel.php`.