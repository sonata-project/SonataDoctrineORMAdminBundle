Form field definition
=====================

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
    use Sonata\AdminBundle\Validator\ErrorElement;

    class PostAdmin extends Admin
    {
        protected function configureFormFields(FormMapper $formMapper)
        {
            $formMapper
                ->add('author', 'sonata_type_model', array(), array('edit' => 'list'))
                ->add('enabled')
                ->add('title')
                ->add('abstract', null, array('required' => false))
                ->add('content')

                // you can define help messages like this
                ->setHelps(array(
                   'title' => $this->trans('help_post_title')
                ));

        }

        public function validate(ErrorElement $errorElement, $object)
        {
            // conditional validation, see the related section for more information
            if ($object->getEnabled()) {
                // abstract cannot be empty when the post is enabled
                $errorElement
                    ->with('abstract')
                        ->assertNotBlank()
                        ->assertNotNull()
                    ->end()
                ;
            }
        }
    }

.. note::

    By default, the form framework always sets ``required=true`` for each
    field. This can be an issue for HTML5 browsers as they provide client-side
    validation.


Types available
---------------

    - array
    - checkbox
    - choice
    - decimal
    - integer
    - text
    - date
    - time
    - datetime

If no type is set, the Admin class will use the one set in the doctrine mapping
definition.

Short Object Placeholder
------------------------

When using Many-to-One or One-to-One relations with Sonata Type fields, a short
object description is used to represent the target object. If no object is selected,
a 'No selection' placeholder will be used. If you want to customize this placeholder,
you can use the corresponding option in the form field definition:

.. code-block:: php

    <?php
    namespace Sonata\NewsBundle\Admin;

    use Sonata\AdminBundle\Admin\Admin;
    use Sonata\AdminBundle\Form\FormMapper;

    class PostAdmin extends Admin
    {
        protected function configureFormFields(FormMapper $formMapper)
        {
            $formMapper
                ->with('General')
                    ->add('enabled', null, array('required' => false))
                    ->add('author', 'sonata_type_model_list', array(
                    ), array(
                        'placeholder' => 'No author selected'
                    ))

            ;
        }
    }

This placeholder is translated using the SonataAdminBundle catalogue.

Advanced Usage: File Management
-------------------------------

If you want to use custom types from the Form framework you must use the
``addType`` method. (The ``add`` method uses the information provided by the
model definition).

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
        protected function configureFormFields(FormMapper $form)
        {
            $formMapper
                ->add('name', null, array('required' => false))
                ->add('enabled', null, array('required' => false))
                ->add('authorName', null, array('required' => false))
                ->add('cdnIsFlushable', null, array('required' => false))
                ->add('description', null, array('required' => false))
                ->add('copyright', null, array('required' => false))
                ->add('binaryContent', 'file', array('required' => false));
        }
  }

.. note::

    By setting ``type=false`` in the file definition, the Form framework will
    provide an instance of ``UploadedFile`` for the ``Media::setBinaryContent``
    method. Otherwise, the full path will be provided.

Advanced Usage: Many-to-one
---------------------------

If you have many ``Post``s linked to one ``User``, then the ``Post`` form should
display a ``User`` field.

The AdminBundle provides 2 options:

 - ``sonata_type_model``: the ``User`` list is set in a select widget with an add button to create a new ``User``
 - ``sonata_type_model_list``: the ``User`` list is set in a model where you can search, select and delete a ``User``.

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
        protected function configureFormFields(FormMapper $formMapper)
        {
            $formMapper
                ->with('General')
                    ->add('enabled', null, array('required' => false))
                    ->add('author', 'sonata_type_model_list', array(
                        'btn_add'       => 'Add author',      //Specify a custom label
                        'btn_list'      => 'button.list',     //which will be translated
                        'btn_delete'    => false,             //or hide the button.
                        'btn_catalogue' => 'SonataNewsBundle' //Custom translation domain for buttons
                    ), array(
                        'placeholder' => 'No author selected'
                    ))
                    ->add('title')
                    ->add('abstract')
                    ->add('content')
                ->end()
                ->with('Tags')
                    ->add('tags', 'sonata_type_model', array('expanded' => true))
                ->end()
                ->with('Options', array('collapsed' => true))
                    ->add('commentsCloseAt')
                    ->add('commentsEnabled', null, array('required' => false))
                    ->add('commentsDefaultStatus', 'choice', array('choices' => Comment::getStatusList()))
                ->end()
            ;
        }
    }



Advanced Usage: One-to-many
---------------------------

Let's say you have a ``Gallery`` that links to some ``Media``s with a join table
``galleryHasMedias``. You can easily add a new ``galleryHasMedias`` row by
defining one of these options:

  - ``edit``: ``inline|standard``, the inline mode allows you to add new rows
  - ``inline``: ``table|standard``, the fields are displayed into table
  - ``sortable``: if the model has a position field, you can enable a drag and
    drop sortable effect by setting ``sortable=field_name``

.. code-block:: php

    <?php
    namespace Sonata\MediaBundle\Admin;

    use Sonata\AdminBundle\Admin\Admin;
    use Sonata\AdminBundle\Form\FormMapper;
    use Sonata\AdminBundle\Datagrid\DatagridMapper;
    use Sonata\AdminBundle\Datagrid\ListMapper;

    class GalleryAdmin extends Admin
    {
        protected function configureFormFields(FormMapper $formMapper)
        {
            $formMapper
                ->add('code')
                ->add('enabled')
                ->add('name')
                ->add('defaultFormat')
                ->add('galleryHasMedias', 'sonata_type_collection', array(), array(
                    'edit' => 'inline',
                    'inline' => 'table',
                    'sortable'  => 'position'
                ))
            ;
        }
    }



By default position row will be rendered, if you want to hide it - you will need to alter child 
admin class and add hidden position field. Use code like 

.. code-block:: php

    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
           ->add('position','hidden',array('attr'=>array("hidden" => true)))
    }
