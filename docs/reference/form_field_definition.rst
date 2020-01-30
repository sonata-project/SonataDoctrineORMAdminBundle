.. index::
    double: Reference; Form field
    single: Definition; Form field

Form field definition
=====================

Example
-------

.. code-block:: php

    namespace Sonata\NewsBundle\Admin;

    use Sonata\AdminBundle\Admin\AbstractAdmin;
    use Sonata\AdminBundle\Form\FormMapper;
    use Sonata\AdminBundle\Form\Type\ModelListType;
    use Sonata\Form\Validator\ErrorElement;

    final class PostAdmin extends AbstractAdmin
    {
        protected function configureFormFields(FormMapper $formMapper)
        {
            $formMapper
                ->add('author', ModelListType::class, [])
                ->add('enabled')
                ->add('title')
                ->add('abstract', null, ['required' => false])
                ->add('content')

                // you can define help messages like this
                ->setHelps([
                   'title' => $this->trans('help_post_title')
                ]);
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
                    ->end();
            }
        }
    }

.. note::

    By default, the form framework always sets ``required=true`` for each field.
    This can be an issue for HTML5 browsers as they provide client-side validation.

Types available
---------------

* `array`,
* `checkbox`,
* `choice`,
* `decimal`,
* `integer`,
* `text`,
* `date`,
* `time`,
* `datetime`.

If no type is set, the `Admin` class will use the one set in the doctrine mapping definition.

Short Object Placeholder
------------------------

When using `Many-to-One` or `One-to-One` relations with `Sonata Type` fields,a short object description
is used to represent the target object.
If no object is selected, a `No selection` placeholder will be used. If you want to customize this placeholder,
you can use the corresponding option in the form field definition::

    namespace Sonata\NewsBundle\Admin;

    use Sonata\AdminBundle\Admin\AbstractAdmin;
    use Sonata\AdminBundle\Form\FormMapper;
    use Sonata\AdminBundle\Form\Type\ModelListType;

    final class PostAdmin extends AbstractAdmin
    {
        protected function configureFormFields(FormMapper $formMapper)
        {
            $formMapper
                ->with('General')
                    ->add('enabled', null, ['required' => false])
                    ->add('author', ModelListType::class, [], [
                        'placeholder' => 'No author selected',
                    ]);
        }
    }

This placeholder is translated using the ``SonataAdminBundle`` catalog.

Advanced usage: File management
-------------------------------

If you want to use custom types from the Form framework you must use the ``addType`` method::

    namespace Sonata\MediaBundle\Admin;

    use Sonata\AdminBundle\Admin\AbstractAdmin;
    use Sonata\AdminBundle\Form\FormMapper;

    final class MediaAdmin extends AbstractAdmin
    {
        protected function configureFormFields(FormMapper $formMapper)
        {
            $formMapper
                ->add('name', null, ['required' => false])
                ->add('enabled', null, ['required' => false])
                ->add('authorName', null, ['required' => false])
                ->add('cdnIsFlushable', null, ['required' => false])
                ->add('description', null, ['required' => false])
                ->add('copyright', null, ['required' => false])
                ->add('binaryContent', 'file', ['required' => false]);
        }
  }

.. note::

    The ``add`` method uses the information provided by the model definition.

.. note::

    By setting ``type=false`` in the file definition, the Form framework will provide an instance of
    ``UploadedFile`` for the ``Media::setBinaryContent`` method. Otherwise, the full path will be provided.

Advanced usage: Many-to-one
---------------------------

If you have many ``Post`` linked to one ``User``, then the ``Post`` form should display a ``User`` field.

The AdminBundle provides 2 options:

* ``Sonata\AdminBundle\Form\Type\ModelType``: the ``User`` list is set in a select widget with an `Add` button to create a new ``User``,
* ``Sonata\AdminBundle\Form\Type\ModelListType``: the ``User`` list is set in a model where you can search, select and delete a ``User``.

The following example shows both types in action::

    namespace Sonata\NewsBundle\Admin;

    use Sonata\AdminBundle\Admin\AbstractAdmin;
    use Sonata\AdminBundle\Form\FormMapper;
    use Sonata\AdminBundle\Form\Type\ModelListType;
    use Sonata\AdminBundle\Form\Type\ModelType;

    final class PostAdmin extends AbstractAdmin
    {
        protected function configureFormFields(FormMapper $formMapper)
        {
            $formMapper
                ->with('General')
                    ->add('enabled', null, ['required' => false])
                    ->add('author', ModelListType::class, [
                        'btn_add'       => 'Add author',       //Specify a custom label
                        'btn_list'      => 'button.list',      //which will be translated
                        'btn_delete'    => false,              //or hide the button.
                        'btn_edit'      => 'Edit',             //Hide add and show edit button when value is set
                        'btn_catalogue' => 'SonataNewsBundle', //Custom translation domain for buttons
                    ], [
                        'placeholder' => 'No author selected',
                    ])
                    ->add('title')
                    ->add('abstract')
                    ->add('content')
                ->end()
                ->with('Tags')
                    ->add('tags', ModelType::class, ['expanded' => true])
                ->end()
                ->with('Options', ['collapsed' => true])
                    ->add('commentsCloseAt')
                    ->add('commentsEnabled', null, ['required' => false])
                    ->add('commentsDefaultStatus', 'choice', [
                        'choices' => Comment::getStatusList()
                    ])
                ->end();
        }
    }

Advanced Usage: One-to-many
---------------------------

Let's say you have a ``Gallery`` that links to some ``Media``.
You can easily add a new ``Media`` row by defining one of these options:

* ``edit``: ``inline|standard``, the inline mode allows you to add new rows,
* ``inline``: ``table|standard``, the fields are displayed into table,
* ``sortable``: if the model has a position field, you can enable a drag and drop sortable effect by setting ``sortable=field_name``.
* ``limit``: ``<an integer>`` if defined, limits the number of elements that can be added, after which the "Add new" button will not be displayed

The following example shows the ``CollectionType`` in action::

    namespace Sonata\MediaBundle\Admin;

    use Sonata\AdminBundle\Admin\AbstractAdmin;
    use Sonata\AdminBundle\Form\FormMapper;
    use Sonata\Form\Type\CollectionType;

    final class GalleryAdmin extends AbstractAdmin
    {
        protected function configureFormFields(FormMapper $formMapper)
        {
            $formMapper
                ->add('code')
                ->add('enabled')
                ->add('name')
                ->add('defaultFormat')
                ->add('galleryHasMedias', CollectionType::class, [
                        'by_reference' => false,
                    ],
                    [
                        'edit' => 'inline',
                        'inline' => 'table',
                        'sortable' => 'position',
                        'limit' => 3,
                ]);
        }
    }

.. note::

    You have to define the ``setMedias`` method into your ``Gallery`` class and manually attach each ``media`` to the current ``gallery`` and define cascading persistence for the relationship from media to gallery.

By default, position row will be rendered. If you want to hide it, you will need to alter child  admin class and add hidden position field.
Use code like::

    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('position', 'hidden', [
                'attr' => ['hidden' => true]
            ]);
    }

To render child help messages you must use 'sonata_help' instead of 'help'::

    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('image', 'file', [
                'sonata_help' => 'help message rendered in parent CollectionType'
            ]);
    }
