.. index::
    double: Reference; Audit
    single: Installation

Audit
=====

The bundle provides optional support for the ``EntityAuditBundle`` from https://github.com/simplethings/EntityAudit.

How it works
------------

**Extract from the original documentation**

There are a bunch of different approaches to auditing or versioning of database tables. This extension creates a
mirroring table for each audited entities table that is suffixed with "_audit". Besides all the columns of the
audited entity there are two additional fields:

* `rev`: contains the global revision number generated from a "revisions" table.
* `revtype`: contains one of 'INS', 'UPD' or 'DEL' as an information to which type of database operation caused this revision log entry.

The global revision table contains an id, timestamp, username and change comment field.

With this approach it is possible to version an application with its changes to associations at the particular points in time.

This extension hooks into the SchemaTool generation process so that it will automatically create the necessary DDL statements for your audited entities.

Installation
------------

The audit functionality is provided by an optional, separated bundle that you need to install:

.. code-block:: bash

    php composer.phar require simplethings/entity-audit-bundle
    
    
Next, be sure to enable the bundle in your `AppKernel.php` file:

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

If the ``EntityAuditBundle`` is enabled, then by default all entities managed by the ``DoctrineORMAdminBundle``
will be audited. You can change this behavior by setting the ``force`` option to ``false`` :

.. code-block:: yaml

    sonata_doctrine_orm_admin:
        audit:
            force: false

It is also possible to configure an entity audit with the attribute `audit` in `services.xml`.
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

Bundle
^^^^^^

Once the ``EntityAuditBundle`` is set, then 3 new actions are available:

* `/admin/vendor/entity/{id}/history`: displays the history list
* `/admin/vendor/entity/{id}/history/{revision}`: displays the object at a specific revision
* `/admin/vendor/entity/{id}/history/{base_revision}/{compare_revision}`: displays a comparision of an object between two specified revisions

These actions are available in the ``view`` and ``edit`` action.
Please note the current implementation uses the ``show`` definition to display the revision.

Entity compare
^^^^^^^^^^^^^^

.. versionadded:: 2.3
    The history compare action was added in SonataAdminBundle 2.3.

For making a comparison of two revisions, the ``show`` definition will be used for rendering both revisions. All rows where the output of the revisions doesn't match, the row is marked.

The ``SonataAdminBundle:CRUD:base_show_field.html.twig`` accepts an optional parameter ``field_compare`` which should contain a secondary field to compare. When assigned, the ``field`` block will be rendered again with the ``field_compare`` value as input.

This means all show_field views should extend ``SonataAdminBundle:CRUD:base_show_field.html.twig`` and should not contain a ``field_compare`` block, since it will automatically use the ``field`` block of the parent view.
