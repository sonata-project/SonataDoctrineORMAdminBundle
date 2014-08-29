.. index::
    double: Reference; Templates
    single: Filter type
    single: Filter field

Filter field definition
=======================

These fields are displayed inside the filter box. They allow you to filter the list of entities by a number of different methods.

A filter instance is always linked to a Form Type, there are 3 types available:

* `sonata_type_filter_number`: display 2 widgets, the operator ( >, >=, <= , <, =) and the value,
* `sonata_type_filter_choice`: display 2 widgets, the operator (yes and no) and the value,
* `sonata_type_filter_default`: display 2 widgets, an hidden operator (can be changed on demand) and the value,
* `sonata_type_filter_date`, not implemented yet!

The `Form Type` configuration is provided by the filter itself.
But they can be tweaked in the ``configureDatagridFilters`` process with the ``add`` method.

The ``add`` method accepts 5 arguments:

* the `field name`,
* the `filter type`, the filter name,
* the `filter options`, the options related to the filter,
* the `field type`, the type of widget used to render the value part,
* the `field options`, the type options.

Filter types available
----------------------

For now, only `Doctrine ORM` filters are available:

* `doctrine_orm_boolean`: depends on the ``sonata_type_filter_default`` Form Type, renders yes or no field,
* `doctrine_orm_callback`: depends on the ``sonata_type_filter_default`` Form Type, types can be configured as needed,
* `doctrine_orm_choice`: depends on the ``sonata_type_filter_choice`` Form Type, renders yes or no field,
* `doctrine_orm_model`: depends on the ``sonata_type_filter_number`` Form Type,
* `doctrine_orm_string`: depends on the ``sonata_type_filter_choice``,
* `doctrine_orm_number`: depends on the ``sonata_type_filter_choice`` Form Type, renders yes or no field,
* `doctrine_orm_date`: depends on the ``sonata_type_filter_date`` Form Type, renders a date field,
* `doctrine_orm_date_range`: depends on the ``sonata_type_filter_date_range`` Form Type, renders a 2 date fields,
* `doctrine_orm_datetime`: depends on the ``sonata_type_filter_datetime`` Form Type, renders a datetime field,
* `doctrine_orm_datetime_range`: depends on the ``sonata_type_filter_datetime_range`` Form Type, renders a 2 datetime fields,
* `doctrine_orm_class`: depends on the ``sonata_type_filter_default`` Form type, renders a choice list field.

Example
-------

.. code-block:: php

    <?php
    namespace Sonata\NewsBundle\Admin;

    use Sonata\AdminBundle\Admin\Admin;
    use Sonata\AdminBundle\Form\FormMapper;
    use Sonata\AdminBundle\Datagrid\DatagridMapper;
    use Sonata\AdminBundle\Datagrid\ListMapper;
    use Sonata\AdminBundle\Show\ShowMapper;

    class PostAdmin extends Admin
    {
        protected function configureDatagridFilters(DatagridMapper $datagrid)
        {
            $datagrid
                ->add('title')
                ->add('enabled')
                ->add('tags', null, array(), null, array('expanded' => true, 'multiple' => true))
            ;
        }
    }

Timestamps
----------

``doctrine_orm_date``, ``doctrine_orm_date_range``, ``doctrine_orm_datetime`` and ``doctrine_orm_datetime_range`` support filtering of timestamp fields by specifying ``'input_type' => 'timestamp'`` option:

.. code-block:: php

    <?php
    namespace Sonata\NewsBundle\Admin;

    use Sonata\AdminBundle\Admin\Admin;
    use Sonata\AdminBundle\Datagrid\DatagridMapper;

    class PostAdmin extends Admin
    {
        protected function configureDatagridFilters(DatagridMapper $datagrid)
        {
            $datagrid
                ->add('timestamp', 'doctrine_orm_datetime_range', array('input_type' => 'timestamp'));
        }
    }

Class
-----

``doctrine_orm_class`` supports filtering on hierarchical entities. You need to specify the ``sub_classes`` option:

.. code-block:: php

    <?php
    namespace Sonata\NewsBundle\Admin;

    use Sonata\AdminBundle\Admin\Admin;
    use Sonata\AdminBundle\Datagrid\DatagridMapper;

    class PostAdmin extends Admin
    {
        protected function configureDatagridFilters(DatagridMapper $datagrid)
        {
            $datagrid->add('type', 'doctrine_orm_class', array('sub_classes' => $this->getSubClasses()));
        }
    }

Advanced usage
--------------

Filtering by sub entity properties
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

If you need to filter your base entities by the value of a sub entity property, you can simply use the dot-separated notation:

.. note::

    This only makes sense when the prefix path is made of entities, not collections.

.. code-block:: php

    <?php
    namespace Acme\AcmeBundle\Admin;

    use Sonata\AdminBundle\Admin\Admin;
    use Sonata\AdminBundle\Form\FormMapper;
    use Sonata\AdminBundle\Datagrid\DatagridMapper;
    use Sonata\AdminBundle\Datagrid\ListMapper;
    use Sonata\AdminBundle\Show\ShowMapper;

    class UserAdmin extends Admin
    {
        protected function configureDatagridFilters(DatagridMapper $datagrid)
        {
            $datagrid
                ->add('id')
                ->add('firstName')
                ->add('lastName')
                ->add('address.street')
                ->add('address.ZIPCode')
                ->add('address.town')
            ;
        }
    }


Label
^^^^^

You can customize the label which appears on the main widget by using a ``label`` option:

.. code-block:: php

    <?php

    protected function configureDatagridFilters(DatagridMapper $datagrid)
    {
        $datagrid
            // ..
            ->add('tags', null, array('label' => 'les tags'), null, array('expanded' => true, 'multiple' => true))
            // ..
        ;
    }


Callback
^^^^^^^^

To create a custom callback filter, two methods need to be implemented:

* one to define the field type,
* one to define how to use the field's value.

The latter shall return whether the filter actually is applied to the queryBuilder or not.
In this example, ``getWithOpenCommentField`` and ``getWithOpenCommentFilter`` implement this functionality:

.. code-block:: php

    <?php
    namespace Sonata\NewsBundle\Admin;

    use Sonata\AdminBundle\Admin\Admin;
    use Sonata\AdminBundle\Form\FormMapper;
    use Sonata\AdminBundle\Datagrid\DatagridMapper;
    use Sonata\AdminBundle\Datagrid\ListMapper;
    use Sonata\AdminBundle\Show\ShowMapper;

    use Application\Sonata\NewsBundle\Entity\Comment;

    class PostAdmin extends Admin
    {
        protected function configureDatagridFilters(DatagridMapper $datagridMapper)
        {
            $datagridMapper
                ->add('title')
                ->add('enabled')
                ->add('tags', null, array(), null, array('expanded' => true, 'multiple' => true))
                ->add('author')
                ->add('with_open_comments', 'doctrine_orm_callback', array(
    //                'callback'   => array($this, 'getWithOpenCommentFilter'),
                    'callback' => function($queryBuilder, $alias, $field, $value) {
                        if (!$value) {
                            return;
                        }

                        $queryBuilder->leftJoin(sprintf('%s.comments', $alias), 'c');
                        $queryBuilder->andWhere('c.status = :status');
                        $queryBuilder->setParameter('status', Comment::STATUS_MODERATE);

                        return true;
                    },
                    'field_type' => 'checkbox'
                ))
            ;
        }

        public function getWithOpenCommentFilter($queryBuilder, $alias, $field, $value)
        {
            if (!$value) {
                return;
            }

            $queryBuilder->leftJoin(sprintf('%s.comments', $alias), 'c');
            $queryBuilder->andWhere('c.status = :status');
            $queryBuilder->setParameter('status', Comment::STATUS_MODERATE);

            return true;
        }
    }
