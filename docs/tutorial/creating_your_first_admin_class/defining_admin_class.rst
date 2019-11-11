.. index::
    double: Tutorial; Admin Class

Defining admin class
====================

The admin class contains all the information required to generate the CRUD interface.
Let's create the Post Admin class.

PostAdmin
---------

By convention, `Admin` files are located in an `Admin` namespace.

First, you need to create an `Admin/PostAdmin.php` file::

    // src/Tutorial/BlogBundle/Admin/PostAdmin.php

    namespace Tutorial\BlogBundle\Admin;

    use Sonata\AdminBundle\Admin\AbstractAdmin;
    use Sonata\AdminBundle\Form\FormMapper;
    use Sonata\AdminBundle\Datagrid\DatagridMapper;
    use Sonata\AdminBundle\Datagrid\ListMapper;
    use Sonata\AdminBundle\Form\Type\ModelType;
    use Sonata\AdminBundle\Show\ShowMapper;

    use Knp\Menu\ItemInterface as MenuItemInterface;

    use Tutorial\BlogBundle\Entity\Comment;

    final class PostAdmin extends AbstractAdmin
    {
        protected function configureShowFields(ShowMapper $showMapper)
        {
            $showMapper
                ->add('enabled')
                ->add('title')
                ->add('author.name')
                ->add('abstract')
                ->add('content')
                ->add('tags');
        }

        protected function configureFormFields(FormMapper $formMapper)
        {
            $formMapper
                ->with('General')
                    ->add('enabled', null, ['required' => false])
                    ->add('title')
                    ->add('abstract')
                    ->add('content')
                ->end()
                ->with('Tags')
                    ->add('tags', ModelType::class, ['expanded' => true, 'multiple' => true])
                ->end()
                ->with('Comments')
                    ->add('comments', ModelType::class, ['multiple' => true])
                ->end()
                ->with('System Information', ['collapsed' => true])
                    ->add('created_at')
                ->end();
        }

        protected function configureListFields(ListMapper $listMapper)
        {
            $listMapper
                ->addIdentifier('title')
                ->add('author.name')
                ->add('enabled')
                ->add('abstract')
                ->add('content')
                ->add('tags')
                ->add('_action', 'actions', [
                    'actions' => [
                        'show' => [],
                        'edit' => [],
                        'delete' => [],
                    ]
                ]);
        }

        protected function configureDatagridFilters(DatagridMapper $datagridMapper)
        {
            $datagridMapper
                ->add('title')
                ->add('enabled')
                ->add('tags', null, ['field_options' => ['expanded' => true, 'multiple' => true]]);
        }
    }

Second, register the `PostAdmin` class inside the DIC in your config file:

.. configuration-block::

    .. code-block:: yaml

        # config/services.yaml

        services:
           tutorial.blog.admin.post:
              class: Tutorial\BlogBundle\Admin\PostAdmin
              arguments: [~, Tutorial\BlogBundle\Entity\Post, TutorialBlogBundle:PostAdmin]
              tags:
                  - { name: sonata.admin, manager_type: orm, group: tutorial_blog, label: 'Post' }

    .. code-block:: xml

        <!-- config/services.xml -->

        <service id="tutorial.blog.admin.post" class="Tutorial\BlogBundle\Admin\PostAdmin">
            <argument/>
            <argument>Tutorial\BlogBundle\Entity\Post</argument>
            <argument>TutorialBlogBundle:PostAdmin</argument>
            <tag name="sonata.admin" manager_type="orm" group="tutorial_blog" label="Post"/>
        </service>

These is the minimal configuration required to display the entity inside the dashboard and interact with the CRUD interface.
Following this however, you will need to create an `Admin Controller`.

This interface will display too many fields as some of them are not relevant to a general overview.
Next we'll see how to specify the fields we want to use and how we want to use them.

So same goes for the `TagAdmin` and `CommentAdmin` class.

Tweak the TagAdmin class
------------------------

.. code-block:: php

    // src/Tutorial/BlogBundle/Admin/TagAdmin.php

    namespace Tutorial\BlogBundle\Admin;

    use Sonata\AdminBundle\Admin\AbstractAdmin;
    use Sonata\AdminBundle\Datagrid\ListMapper;
    use Sonata\AdminBundle\Datagrid\DatagridMapper;
    use Sonata\AdminBundle\Form\FormMapper;
    use Sonata\Form\Validator\ErrorElement;

    use Tutorial\BlogBundle\Entity\Tag;

    final class TagAdmin extends AbstractAdmin
    {
        protected function configureFormFields(FormMapper $formMapper)
        {
            $formMapper
                ->add('name')
                ->add('enabled', null, ['required' => false]);
        }

        protected function configureDatagridFilters(DatagridMapper $datagridMapper)
        {
            $datagridMapper
                ->add('name')
                ->add('posts');
        }

        protected function configureListFields(ListMapper $listMapper)
        {
            $listMapper
                ->addIdentifier('name')
                ->add('enabled');
        }

        public function validate(ErrorElement $errorElement, $object)
        {
            $errorElement
                ->with('name')
                    ->assertMaxLength(['limit' => 32])
                ->end();
        }
    }

And register the `TagAdmin` class inside the DIC in your config file:

.. code-block:: yaml

    # config/services.yaml

    services:
        tutorial.blog.admin.tag:
            class: Tutorial\BlogBundle\Admin\TagAdmin
            arguments: [~, Tutorial\BlogBundle\Entity\Tag, TutorialBlogBundle:TagAdmin]
            tags:
                - { name: sonata.admin, manager_type: orm, group: tutorial_blog, label: 'Tag' }

Tweak the CommentAdmin class
----------------------------

.. code-block:: php

    // src/Tutorial/BlogBundle/Admin/CommentAdmin.php

    namespace Tutorial\BlogBundle\Admin;

    use Sonata\AdminBundle\Admin\AbstractAdmin;
    use Sonata\AdminBundle\Form\FormMapper;
    use Sonata\AdminBundle\Form\Type\ModelType;
    use Sonata\AdminBundle\Datagrid\DatagridMapper;
    use Sonata\AdminBundle\Datagrid\ListMapper;

    use Application\Sonata\NewsBundle\Entity\Comment;

    final class CommentAdmin extends AbstractAdmin
    {
        protected $parentAssociationMapping = 'post';

        protected function configureFormFields(FormMapper $formMapper)
        {
            if (!$this->isChild()) {
                $formMapper->add('post', ModelType::class, [], ['edit' => 'list']);
            }

            $formMapper
                ->add('name')
                ->add('email')
                ->add('url', null, ['required' => false])
                ->add('message');
        }

        protected function configureDatagridFilters(DatagridMapper $datagridMapper)
        {
            $datagridMapper
                ->add('name')
                ->add('email')
                ->add('message');
        }

        protected function configureListFields(ListMapper $listMapper)
        {
            $listMapper
                ->addIdentifier('name')
                ->add('post')
                ->add('email')
                ->add('url')
                ->add('message');
        }

        public function getBatchActions()
        {
            $actions = parent::getBatchActions();

            $actions['enabled'] = [
                'label' => $this->trans('batch_enable_comments'),
                'ask_confirmation' => true,
            ];

            $actions['disabled'] = [
                'label' => $this->trans('batch_disable_comments'),
                'ask_confirmation' => false
            ];

            return $actions;
        }
    }

And register the `TagAdmin` class inside the DIC in your config file:

.. code-block:: yaml

    # config/services.yaml

    services:
        tutorial.blog.admin.comment:
            class: Tutorial\BlogBundle\Admin\CommentAdmin
            arguments: [, Tutorial\BlogBundle\Entity\Comment, TutorialBlogBundle:CommentAdmin]
            tags:
                - { name: sonata.admin, manager_type: orm, group: tutorial_blog, label: 'Comment' }
