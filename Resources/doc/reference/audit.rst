Audit
=====

The bundle provides optional support for the ``EntityAuditBundle`` from https://github.com/simplethings/EntityAudit.

How it works
------------

**Extract from the original documentation**

There are a bunch of different approaches to auditing or versioning of database tables. This extension creates a
mirroring table for each audited entities table that is suffixed with "_audit". Besides all the columns of the
audited entity there are two additional fields:

- rev : Contains the global revision number generated from a "revisions" table.
- revtype : Contains one of 'INS', 'UPD' or 'DEL' as an information to which type of database operation caused
  this revision log entry.

The global revision table contains an id, timestamp, username and change comment field.

With this approach it is possible to version an application with its changes to associations at the particular
points in time.

This extension hooks into the SchemaTool generation process so that it will automatically create the necessary
DDL statements for your audited entities.

Installation
------------

The audit functionality is provided by an optional, separate bundle that you need to install:

.. code-block:: bash

    php composer.phar require simplethings/entity-audit-bundle
    
    
Next, be sure to enable the bundle in your AppKernel.php file:

.. code-block:: php

    <?php
    // app/AppKernel.php
    public function registerBundles()
    {
        return array(
            // ...
            new SimpleThings\EntityAudit\SimpleThingsEntityAuditBundle(),
            // ...
        );
    }

Configuration
-------------

If the ``EntityAuditBundle`` is enabled, then all entities managed by the ``DoctrineORMAdminBundle`` will be audited.

It is possible to disable an entity to be audited with the attribute audit="false" in services.xml
For instance :

.. code-block:: xml

    <service id="tutorial.blog.admin.post" class="Tutorial\BlogBundle\Admin\PostAdmin">
        <tag name="sonata.admin" manager_type="orm" audit="false" group="tutorial_blog" label="post"/>

        <argument/>
        <argument>Tutorial\BlogBundle\Entity\Post</argument>
        <argument>TutorialBlogBundle:PostAdmin</argument>
    </service>


Usage
-----

Once the ``EntityAuditBundle`` is set, then 2 new actions are availables :

 - /admin/vendor/entity/{id}/history : display the history list
 - /admin/vendor/entity/{id}/history/{revision} : display the object at a specific revision

These actions are available in the ``view`` and ``edit`` action. Please note the current implementation uses
the ``show`` definition to display the revision.
