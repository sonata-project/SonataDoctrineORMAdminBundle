Installation
============

Download bundles
----------------

To begin, add the dependent bundles to the ``vendor/bundles`` directory. Add
the following lines to the file ``deps``::

  [SonatajQueryBundle]
      git=http://github.com/sonata-project/SonatajQueryBundle.git
      target=/bundles/Sonata/jQueryBundle

  [SonataUserBundle]
      git=http://github.com/sonata-project/SonataUserBundle.git
      target=/bundles/Sonata/UserBundle

  [SonataAdminBundle]
      git=http://github.com/sonata-project/SonataAdminBundle.git
      target=/bundles/Sonata/AdminBundle

  [KnpMenuBundle]
      git=https://github.com/KnpLabs/KnpMenuBundle.git
      target=/bundles/Knp/Bundle/MenuBundle

  [KnpMenu]
      git=https://github.com/KnpLabs/KnpMenu.git
      target=/knp/menu

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
  // app/autoload.php
  $loader->registerNamespaces(array(
      // ...
      'Sonata'                         => __DIR__.'/../vendor/bundles',
      'Knp\Bundle'                     => __DIR__.'/../vendor/bundles',
      'Knp\Menu'                       => __DIR__.'/../vendor/knp/menu/src',
      // ...
  ));

  // app/AppKernel.php
  public function registerBundles()
  {
      return array(
          // ...
          new Sonata\jQueryBundle\SonatajQueryBundle(),
          new Sonata\AdminBundle\SonataAdminBundle(),
          new Knp\Bundle\MenuBundle\KnpMenuBundle(),
          new Sonata\DoctrineORMAdminBundle\SonataDoctrineORMAdminBundle(),
          // ...
      );
  }

The bundle also contains several routes. Import them by adding the following
code to your application's routing file:

.. code-block:: yaml

    # app/config/routing.yml
    admin:
        resource: '@SonataAdminBundle/Resources/config/routing/sonata_admin.xml'
        prefix: /admin

    _sonata_admin:
        resource: .
        type: sonata_admin
        prefix: /admin

The last step is to generate the blog bundle in which we will work.

  php app/console generate:bundle --namespace=Tutorial/BlogBundle

And we enable it:

.. code-block:: php

  <?php
  // app/autoload.php
  $loader->registerNamespaces(array(
      // ...
      'Tutorial'      => __DIR__.'/../src',
      // ...
  ));

At this point you can access to the dashboard with the url:

  http://yoursite.local/admin/dashboard

