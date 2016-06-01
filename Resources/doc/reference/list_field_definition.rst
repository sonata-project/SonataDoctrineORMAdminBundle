.. index::
    double: Reference; List field
    single: Template
    single: Usage
    single: Actions

List field definition
=====================

These fields are used to display the information inside the list table.

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
        protected function configureListFields(ListMapper $listMapper)
        {
            $listMapper
                ->addIdentifier('title')
                ->add('author')
                ->add('enabled')
                ->add('tags')
                ->add('commentsEnabled')

                // add custom action links
                ->add('_action', 'actions', array(
                    'actions' => array(
                        'show' => array(),
                        'edit' => array(),
                    )
                ))
            ;
        }
    }

Types available
---------------

The most important option for each field is the ``type``. The available `types` include:

* `boolean`,
* `datetime`,
* `decimal`,
* `identifier`,
* `integer`,
* `many_to_one`,
* `string`,
* `text`,
* `date`,
* `time`,
* `array`.

.. note::

    For the `many_to_one` type, a link will be added to the related `Edit` action.

.. note::

    Entities with a class as identifier value (ex. `uuid <https://github.com/ramsey/uuid>`_) will resolve to the correct supported type.

    .. code-block:: php

        <?php
        use Ramsey\Uuid\Uuid;

        class Example
        {
            /**
             * @var \Ramsey\Uuid\Uuid
             *
             * @ORM\Column(type="uuid")
             * @ORM\Id
             */
            private $id;

            public function __construct()
            {
                $this->id = Uuid::uuid4();
            }
        }

If no type is set, the ``Admin`` class will use the type defined in the Doctrine mapping definition.

List actions
------------

You can set actions for the list items by adding an '_action' field in ``configureListFields``:

.. code-block:: php

    <?php
    $listMapper->add('_action', 'actions', array(
        'actions' => array(
            'show' => array(),
            'edit' => array(),
        )
    ))

`Edit` and `Delete` actions are enabled in the default configuration. You can add your own!
Default template file is: ``SonataAdminBundle:CRUD:list__action_[ACTION_NAME].html.twig``

You can specify your own by setting up the 'template' option like so:

.. code-block:: php

    <?php
    $listMapper->add('_action', 'actions', array(
        'actions' => array(
            'show' => array(),
            'edit' => array(),
            'delete' => array('template' => 'MyBundle:MyController:my_partial.html.twig'),
        )
    ))

Advance Usage
-------------

Displaying sub entity properties
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

If you need to display only one field from a sub entity or embedded object in a dedicated column, you can simply use the dot-separated notation:

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
        protected function configureListFields(ListMapper $listMapper)
        {
            $listMapper
                ->addIdentifier('id')
                ->addIdentifier('firstName')
                ->addIdentifier('lastName')
                ->addIdentifier('address.street')
                ->addIdentifier('address.ZIPCode')
                ->addIdentifier('address.town')
            ;
        }
    }


Custom template
^^^^^^^^^^^^^^^

If you need a specific layout for a row cell, you can define a custom template:

.. code-block:: php

    <?php
    namespace Sonata\MediaBundle\Admin;

    use Sonata\AdminBundle\Admin\Admin;
    use Sonata\AdminBundle\Form\FormMapper;
    use Sonata\AdminBundle\Datagrid\DatagridMapper;
    use Sonata\AdminBundle\Datagrid\ListMapper;
    use Sonata\AdminBundle\Show\ShowMapper;

    class MediaAdmin extends Admin
    {
        protected function configureListFields(ListMapper $listMapper)
        {
            $listMapper
                ->addIdentifier('id')
                ->add('image', 'string', array('template' => 'SonataMediaBundle:MediaAdmin:list_image.html.twig'))
                ->add('custom', 'string', array('template' => 'SonataMediaBundle:MediaAdmin:list_custom.html.twig'))
            ;
        }
    }

The related template:

.. code-block:: jinja

    {% extends 'SonataAdminBundle:CRUD:base_list_field.html.twig' %}

    {% block field %}
        <div>
            <strong>{{ object.name }}</strong> <br />
            {{ object.providername}} : {{ object.width }}x{{ object.height }} <br />
        </div>
    {% endblock %}
	
Custom route
^^^^^^^^^^^^

Default route for a link is `edit` (`many_to_one`, `one_to_one`, etc.).
Using this, the route can be customized as follows:

.. code-block:: php

    <?php
    namespace Sonata\MediaBundle\Admin;

    use Sonata\AdminBundle\Admin\Admin;
    use Sonata\AdminBundle\Form\FormMapper;
    use Sonata\AdminBundle\Datagrid\DatagridMapper;
    use Sonata\AdminBundle\Datagrid\ListMapper;
    use Sonata\AdminBundle\Show\ShowMapper;


    class MediaAdmin extends Admin
    {
        protected function configureListFields(ListMapper $listMapper)
        {
            $listMapper
                ->addIdentifier('field', null, array(
                    'route' => array(
                        'name' => 'show'
                    )
                ))
                ;
        }
   }

