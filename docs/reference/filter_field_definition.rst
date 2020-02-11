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

* the `field name`, fields of relations (of relations of relations … ) can be
  specified with a dot-separated syntax.
* the `filter type`, the filter name,
* the `filter options`, the options related to the filter,
* the `field type`, the type of widget used to render the value part,
* the `field options`, the type options.

Available filter types
----------------------

For now, only `Doctrine ORM` filters are available:

* ``Sonata\DoctrineORMAdminBundle\Filter\BooleanFilter``: depends on the ``sonata_type_filter_default`` Form Type, renders yes or no field,
* ``Sonata\DoctrineORMAdminBundle\Filter\CallbackFilter``: depends on the ``sonata_type_filter_default`` Form Type, types can be configured as needed,
* ``Sonata\DoctrineORMAdminBundle\Filter\ChoiceFilter``: depends on the ``sonata_type_filter_choice`` Form Type, renders yes or no field,
* ``Sonata\DoctrineORMAdminBundle\Filter\NumberFilter``: depends on the ``sonata_type_filter_number`` Form Type,
* ``Sonata\DoctrineORMAdminBundle\Filter\ModelAutocompleteFilter``: uses ``sonata_type_model_autocomplete`` form type, can be used as replacement of ``Sonata\DoctrineORMAdminBundle\Filter\ModelFilter`` to handle too many items that cannot be loaded into memory.
* ``Sonata\DoctrineORMAdminBundle\Filter\StringFilter``: depends on the ``sonata_type_filter_choice``,
* ``Sonata\DoctrineORMAdminBundle\Filter\NumberFilter``: depends on the ``sonata_type_filter_choice`` Form Type, renders yes or no field,
* ``Sonata\DoctrineORMAdminBundle\Filter\DateFilter``: depends on the ``sonata_type_filter_date`` Form Type, renders a date field,
* ``Sonata\DoctrineORMAdminBundle\Filter\DateRangeFilter``: depends on the ``sonata_type_filter_date_range`` Form Type, renders a 2 date fields,
* ``Sonata\DoctrineORMAdminBundle\Filter\DateTimeFilter``: depends on the ``sonata_type_filter_datetime`` Form Type, renders a datetime field,
* ``Sonata\DoctrineORMAdminBundle\Filter\DateTimeRangeFilter``: depends on the ``sonata_type_filter_datetime_range`` Form Type, renders a 2 datetime fields,
* ``Sonata\DoctrineORMAdminBundle\Filter\ClassFilter``: depends on the ``sonata_type_filter_default`` Form type, renders a choice list field.

Example
-------

.. code-block:: php

    namespace Sonata\NewsBundle\Admin;

    use Sonata\AdminBundle\Admin\Abstractdmin;
    use Sonata\AdminBundle\Datagrid\DatagridMapper;

    final class PostAdmin extends AbstractAdmin
    {
        protected function configureDatagridFilters(DatagridMapper $datagridMapper)
        {
            $datagridMapper
                ->add('title')
                ->add('enabled')
                ->add('tags', null, [], null, ['expanded' => true, 'multiple' => true]);
        }
    }

doctrine_orm_model_autocomplete
-------------------------------

This filter type uses ``Sonata\AdminBundle\Form\Type\ModelAutocompleteType`` form type. It renders an input with select2 autocomplete feature.
Can be used as replacement of ``Sonata\DoctrineORMAdminBundle\Filter\ModelFilter`` to handle too many related items that cannot be loaded into memory.
This form type requires ``property`` option. See documentation of ``Sonata\AdminBundle\Form\Type\ModelAutocompleteType`` for all available options for this form type::

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('category', ModelAutocompleteFilter::class, [], null, [
                // in related CategoryAdmin there must be datagrid filter on `title` field to make the autocompletion work
                'property'=>'title',
            ]);
    }

doctrine_orm_date_range
-----------------------

The ``Sonata\DoctrineORMAdminBundle\Filter\DateRangeFilter`` filter renders two fields to filter all records between two dates.
If only one date is set it will filter for all records until or since the given date::

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper->add('created', DateRangeFilter::class);
    }

Timestamps
----------

``Sonata\DoctrineORMAdminBundle\Filter\DateFilter``, ``Sonata\DoctrineORMAdminBundle\Filter\DateRangeFilter``, ``Sonata\DoctrineORMAdminBundle\Filter\DateTimeFilter`` and ``Sonata\DoctrineORMAdminBundle\Filter\DateTimeRangeFilter``
support filtering of timestamp fields by specifying ``'input_type' => 'timestamp'`` option::

    namespace Sonata\NewsBundle\Admin;

    use Sonata\AdminBundle\Admin\AbstractAdmin;
    use Sonata\AdminBundle\Datagrid\DatagridMapper;
    use Sonata\DoctrineORMAdminBundle\Filter\DateTimeRangeFilter;

    final class PostAdmin extends AbstractAdmin
    {
        protected function configureDatagridFilters(DatagridMapper $datagridMapper)
        {
            $datagridMapper
                ->add('timestamp', DateTimeRangeFilter::class, ['input_type' => 'timestamp']);
        }
    }

Class
-----

``Sonata\DoctrineORMAdminBundle\Filter\ClassFilter`` supports filtering on hierarchical entities. You need to specify the ``sub_classes`` option::

    namespace Sonata\NewsBundle\Admin;

    use Sonata\AdminBundle\Admin\AbstractAdmin;
    use Sonata\AdminBundle\Datagrid\DatagridMapper;
    use Sonata\DoctrineORMAdminBundle\Filter\ClassFilter;

    final class PostAdmin extends AbstractAdmin
    {
        protected function configureDatagridFilters(DatagridMapper $datagridMapper)
        {
            $datagridMapper
                ->add('type', ClassFilter::class, ['sub_classes' => $this->getSubClasses()]);
        }
    }

Advanced usage
--------------

Filtering by sub entity properties
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

If you need to filter your base entities by the value of a sub entity property, you can simply use the dot-separated notation::

    namespace App\Admin;

    use Sonata\AdminBundle\Admin\AbstractAdmin;
    use Sonata\AdminBundle\Datagrid\DatagridMapper;

    final class UserAdmin extends AbstractAdmin
    {
        protected function configureDatagridFilters(DatagridMapper $datagridMapper)
        {
            $datagridMapper
                ->add('id')
                ->add('firstName')
                ->add('lastName')
                ->add('address.street')
                ->add('address.ZIPCode')
                ->add('address.town');
        }
    }

.. note::

    This only makes sense when the prefix path is made of entities, not collections.

Label
^^^^^

You can customize the label which appears on the main widget by using a ``label`` option::

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('tags', null, ['label' => 'les tags'], null, ['expanded' => true, 'multiple' => true]);
    }

Callback
^^^^^^^^

To create a custom callback filter, two methods need to be implemented:

* one to define the field type,
* one to define how to use the field's value.

The latter shall return whether the filter actually is applied to the queryBuilder or not.
In this example, ``getWithOpenCommentField`` and ``getWithOpenCommentFilter`` implement this functionality::

    namespace Sonata\NewsBundle\Admin;

    use Sonata\AdminBundle\Admin\AbstractAdmin;
    use Sonata\AdminBundle\Datagrid\DatagridMapper;
    use Sonata\DoctrineORMAdminBundle\Filter\CallbackFilter;
    use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

    use Application\Sonata\NewsBundle\Entity\Comment;

    final class PostAdmin extends AbstractAdmin
    {
        protected function configureDatagridFilters(DatagridMapper $datagridMapper)
        {
            $datagridMapper
                ->add('title')
                ->add('enabled')
                ->add('tags', null, [], null, ['expanded' => true, 'multiple' => true])
                ->add('author')
                ->add('with_open_comments', CallbackFilter::class, [
    //                'callback'   => [$this, 'getWithOpenCommentFilter'],
                    'callback' => function($queryBuilder, $alias, $field, $value) {
                        if (!$value['value']) {
                            return;
                        }

                        $queryBuilder
                            ->leftJoin(sprintf('%s.comments', $alias), 'c')
                            ->andWhere('c.status = :status')
                            ->setParameter('status', Comment::STATUS_MODERATE);

                        return true;
                    },
                    'field_type' => CheckboxType::class
                ]);
        }

        public function getWithOpenCommentFilter($queryBuilder, $alias, $field, $value)
        {
            if (!$value['value']) {
                return;
            }

            $queryBuilder
                ->leftJoin(sprintf('%s.comments', $alias), 'c')
                ->andWhere('c.status = :status')
                ->setParameter('status', Comment::STATUS_MODERATE);

            return true;
        }
    }
