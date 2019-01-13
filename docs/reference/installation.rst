.. index::
    double: Reference; Installation

Installation
============

``SonataDoctrineORMAdminBundle`` is part of a set of bundles aimed at abstracting storage connectivity for ``SonataAdminBundle``.
As such, ``SonataDoctrineORMAdminBundle`` depends on ``SonataAdminBundle`` and will not work without it.

.. note::

    These installation instructions are meant to be used only as part of SonataAdminBundle's installation process,
    which is documented `here <https://sonata-project.org/bundles/admin/master/doc/reference/installation.html>`_.

Download the bundle
-------------------

.. code-block:: bash

    composer require sonata-project/doctrine-orm-admin-bundle

Enable the bundle
-----------------

Next, be sure to enable the bundles in your ``bundles.php`` file if they
are not already enabled::

    // config/bundles.php

    return [
        // ...
        Sonata\DoctrineORMAdminBundle\SonataDoctrineORMAdminBundle::class => ['all' => true],
    ];

.. note::

    Don't forget that, as part of `SonataAdminBundle's installation instructions <https://sonata-project.org/bundles/admin/master/doc/reference/installation.html>`_,
    you need to enable additional bundles on `bundles.php`.
