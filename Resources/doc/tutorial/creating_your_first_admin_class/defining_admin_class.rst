Defining admin class
====================

The admin class contains all the information required to generate the CRUD
interface. Let's create the Post Admin class.

PostAdmin
---------

By convention, Admin files are located in an Admin namespace.

First, you need to create an Admin/PostAdmin.php file

.. code-block:: php

    <?php
    // src/Tutorial/BlogBundle/Admin/PostAdmin.php
    namespace Tutorial\BlogBundle\Admin;

    use Sonata\AdminBundle\Admin\Admin;
    use Sonata\AdminBundle\Form\FormMapper;
    use Sonata\AdminBundle\Datagrid\DatagridMapper;
    use Sonata\AdminBundle\Datagrid\ListMapper;
    use Sonata\AdminBundle\Show\ShowMapper;

    use Knp\Menu\ItemInterface as MenuItemInterface;

    use Tutorial\BlogBundle\Entity\Comment;

    class PostAdmin extends Admin
    {
        /**
         * @param \Sonata\AdminBundle\Show\ShowMapper $showMapper
         *
         * @return void
         */
        protected function configureShowField(ShowMapper $showMapper)
        {
            $showMapper
                ->add('enabled')
                ->add('title')
                ->add('abstract')
                ->add('content')
                ->add('tags')
            ;
        }

        /**
         * @param \Sonata\AdminBundle\Form\FormMapper $formMapper
         *
         * @return void
         */
        protected function configureFormFields(FormMapper $formMapper)
        {
            $formMapper
                ->with('General')
                    ->add('enabled', null, array('required' => false))
                    ->add('title')
                    ->add('abstract')
                    ->add('content')
                ->end()
                ->with('Tags')
                    ->add('tags', 'sonata_type_model', array('expanded' => true, 'multiple' => true))
                ->end()
                ->with('Comments')
                    ->add('comments', 'sonata_type_model', array('multiple' => true))
                ->end()
                ->with('System Information', array('collapsed' => true))
                    ->add('created_at')
                ->end()
            ;
        }

        /**
         * @param \Sonata\AdminBundle\Datagrid\ListMapper $listMapper
         *
         * @return void
         */
        protected function configureListFields(ListMapper $listMapper)
        {
            $listMapper
                ->addIdentifier('title')
                ->add('enabled')
                ->add('abstract')
                ->add('content')
                ->add('tags')
                ->add('_action', 'actions', array(
                    'actions' => array(
                        'show' => array(),
                        'edit' => array(),
                        'delete' => array(),
                    )
                ))
            ;
        }

        /**
         * @param \Sonata\AdminBundle\Datagrid\DatagridMapper $datagridMapper
         *
         * @return void
         */
        protected function configureDatagridFilters(DatagridMapper $datagridMapper)
        {
            $datagridMapper
                ->add('title')
                ->add('enabled')
                ->add('tags', null, array('field_options' => array('expanded' => true, 'multiple' => true)))
            ;
        }
    }

Second, register the PostAdmin class inside the DIC in your config file:

.. code-block:: yaml

    # app/config/config.yml
    services:
       tutorial.blog.admin.post:
          class: Tutorial\BlogBundle\Admin\PostAdmin
          tags:
            - { name: sonata.admin, manager_type: orm, group: tutorial_blog, label: post }
          arguments: [null, Tutorial\BlogBundle\Entity\Post, TutorialBlogBundle:PostAdmin]

Or if you're using a XML configuration file:

.. code-block:: xml

    <service id="tutorial.blog.admin.post" class="Tutorial\BlogBundle\Admin\PostAdmin">
        <tag name="sonata.admin" manager_type="orm" group="tutorial_blog" label="post"/>

        <argument/>
        <argument>Tutorial\BlogBundle\Entity\Post</argument>
        <argument>TutorialBlogBundle:PostAdmin</argument>
    </service>


These is the minimal configuration required to display the entity inside the
dashboard and interact with the CRUD interface. Following this however, you will
need to create an admin Controller.

This interface will display too many fields as some of them are not relevant to
a general overview. Next we'll see how to specify the fields we want to use and
how we want to use them.

So same goes for the TagAdmin and CommentAdmin class.

Tweak the TagAdmin class
------------------------

.. code-block:: php

    <?php
    // src/Tutorial/BlogBundle/Admin/TagAdmin.php
    namespace Tutorial\BlogBundle\Admin;

    use Sonata\AdminBundle\Admin\Admin;
    use Sonata\AdminBundle\Datagrid\ListMapper;
    use Sonata\AdminBundle\Datagrid\DatagridMapper;
    use Sonata\AdminBundle\Validator\ErrorElement;
    use Sonata\AdminBundle\Form\FormMapper;

    use Tutorial\BlogBundle\Entity\Tag;

    class TagAdmin extends Admin
    {
        /**
         * @param \Sonata\AdminBundle\Form\FormMapper $formMapper
         * @return void
         */
        protected function configureFormFields(FormMapper $formMapper)
        {
            $formMapper
                ->add('name')
                ->add('enabled', null, array('required' => false))
            ;
        }

        /**
         * @param \Sonata\AdminBundle\Datagrid\DatagridMapper $datagridMapper
         * @return void
         */
        protected function configureDatagridFilters(DatagridMapper $datagridMapper)
        {
            $datagridMapper
                ->add('name')
                ->add('posts')
            ;
        }

        /**
         * @param \Sonata\AdminBundle\Datagrid\ListMapper $listMapper
         * @return void
         */
        protected function configureListFields(ListMapper $listMapper)
        {
            $listMapper
                ->addIdentifier('name')
                ->add('enabled')
            ;
        }

        /**
         * @param \Sonata\AdminBundle\Validator\ErrorElement $errorElement
         * @param mixed $object
         * @return void
         */
        public function validate(ErrorElement $errorElement, $object)
        {
            $errorElement
                ->with('name')
                    ->assertMaxLength(array('limit' => 32))
                ->end()
            ;
        }
    }

And register the TagAdmin class inside the DIC in your config file:

.. code-block:: yaml

    # app/config/config.yml
    services:
       #...
       tutorial.blog.admin.tag:
          class: Tutorial\BlogBundle\Admin\TagAdmin
          tags:
            - { name: sonata.admin, manager_type: orm, group: tutorial_blog, label: tag }
          arguments: [null, Tutorial\BlogBundle\Entity\Tag, TutorialBlogBundle:TagAdmin]


Tweak the CommentAdmin class
----------------------------

.. code-block:: php

    <?php
    // src/Tutorial/BlogBundle/Admin/CommentAdmin.php
    namespace Tutorial\BlogBundle\Admin;

    use Sonata\AdminBundle\Admin\Admin;
    use Sonata\AdminBundle\Form\FormMapper;
    use Sonata\AdminBundle\Datagrid\DatagridMapper;
    use Sonata\AdminBundle\Datagrid\ListMapper;

    use Application\Sonata\NewsBundle\Entity\Comment;

    class CommentAdmin extends Admin
    {
        protected $parentAssociationMapping = 'post';

        /**
         * @param \Sonata\AdminBundle\Form\FormMapper $formMapper
         * @return void
         */
        protected function configureFormFields(FormMapper $formMapper)
        {
            if(!$this->isChild()) {
                $formMapper->add('post', 'sonata_type_model', array(), array('edit' => 'list'));
            }

            $formMapper
                ->add('name')
                ->add('email')
                ->add('url', null, array('required' => false))
                ->add('message')
            ;
        }

        /**
         * @param \Sonata\AdminBundle\Datagrid\DatagridMapper $datagridMapper
         * @return void
         */
        protected function configureDatagridFilters(DatagridMapper $datagridMapper)
        {
            $datagridMapper
                ->add('name')
                ->add('email')
                ->add('message')
            ;
        }

        /**
         * @param \Sonata\AdminBundle\Datagrid\ListMapper $listMapper
         * @return void
         */
        protected function configureListFields(ListMapper $listMapper)
        {
            $listMapper
                ->addIdentifier('name')
                ->add('post')
                ->add('email')
                ->add('url')
                ->add('message');
        }

        /**
         * @return array
         */
        public function getBatchActions()
        {
            $actions = parent::getBatchActions();

            $actions['enabled'] = array(
                'label' => $this->trans('batch_enable_comments'),
                'ask_confirmation' => true,
            );

            $actions['disabled'] = array(
                'label' => $this->trans('batch_disable_comments'),
                'ask_confirmation' => false
            );

            return $actions;
        }
    }

And register the TagAdmin class inside the DIC in your config file:

.. code-block:: yaml

    # app/config/config.yml
    services:
       #...
       tutorial.blog.admin.comment:
          class: Tutorial\BlogBundle\Admin\CommentAdmin
          tags:
            - { name: sonata.admin, manager_type: orm, group: tutorial_blog, label: comment }
          arguments: [null, Tutorial\BlogBundle\Entity\Comment, TutorialBlogBundle:CommentAdmin]
