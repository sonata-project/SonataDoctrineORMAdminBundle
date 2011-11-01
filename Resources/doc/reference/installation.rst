Installation
============

First install the Sonata Admin Bundle

Download bundle
---------------

Add the following lines to the file ``deps``::

  [SonataDoctrineORMAdminBundle]
      git=http://github.com/sonata-project/SonataDoctrineORMAdminBundle.git
      target=/bundles/Sonata/DoctrineORMAdminBundle

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
          // ...
      );
  }

