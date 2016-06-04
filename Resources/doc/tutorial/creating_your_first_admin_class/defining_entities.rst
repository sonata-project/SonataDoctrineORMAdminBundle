.. index::
    single: Model
    double: Post; Definition
    double: Comment; Definition
    double: Tag; Definition
    double: Tutorial; Entity

Defining Entities
=================

This tutorial uses the more verbose `xml` format of defining entities, but any metadata driver will work fine.
The ``AdminBundle`` simply interacts with the entities as provided by Doctrine.

Model definition
----------------

Now we need to create the entities that will be used in the blog:

Author
~~~~~~

.. code-block:: php

    <?php
    // src/Tutorial/BlogBundle/Entity/Author.php
    namespace Tutorial\BlogBundle\Entity;

    use Doctrine\ORM\Mapping as ORM;

    /**
     * @ORM\Embeddable
     */
    class Author
    {
        /**
         * @ORM\Column(type = "string")
         */
        protected $name;

        public function __construct($name)
        {
            $this->name = $name;
        }

        public function getName()
        {
            return $this->name;
        }
    }

Post
~~~~

.. code-block:: php

    <?php

    // src/Tutorial/BlogBundle/Entity/Post.php
    namespace Tutorial\BlogBundle\Entity;

    use Doctrine\ORM\Mapping as ORM;
    use Symfony\Component\Validator\Constraints as Assert;

    /**
     * @ORM\Entity
     */
    class Post
    {
        /**
         * @ORM\Id
         * @ORM\Column(type="integer")
         * @ORM\GeneratedValue(strategy="AUTO")
         */
        protected $id;

        /**
         * @ORM\Column(type="string", length=255)
         *
         * @Assert\NotBlank()
         * @Assert\Length(min="10", max=255)
         */
        protected $title;

        /**
         * @ORM\Column(type="text")
         */
        protected $abstract;

        /**
         * @ORM\Column(type="text")
         *
         * @Assert\NotBlank()
         */
        protected $content;

        /**
         * @ORM\Column(type="boolean")
         */
        protected $enabled;

        /**
         * @ORM\Column(type="datetime")
         */
        protected $created_at;

        /**
         * @ORM\OneToMany(targetEntity="Comment", mappedBy="post")
         */
        protected $comments;

        /**
         * @ORM\ManyToMany(targetEntity="Tag")
         */
        protected $tags;

        /**
         * @ORM\Embedded(class="Author")
         */
        protected $author;

        public function __construct()
        {
            $this->tags     = new \Doctrine\Common\Collections\ArrayCollection();
            $this->comments = new \Doctrine\Common\Collections\ArrayCollection();
            $this->created_at = new \DateTime("now");
            $this->author = new Author('admin');
        }

        public function __toString()
        {
            return $this->getTitle();
        }

        public function getAuthor()
        {
            return $this->author;
        }
    }

Tag
~~~

.. code-block:: php

    <?php

    // src/Tutorial/BlogBundle/Entity/Tag.php
    namespace Tutorial\BlogBundle\Entity;

    use Doctrine\ORM\Mapping as ORM;
    use Symfony\Component\Validator\Constraints as Assert;

    /**
     * @ORM\Entity
     */
    class Tag
    {
        /**
         * @ORM\Id
         * @ORM\Column(type="integer")
         * @ORM\GeneratedValue(strategy="AUTO")
         */
        protected $id;

        /**
         * @ORM\Column(type="string")
         * @Assert\NotBlank()
         */
        protected $name;

        /**
         * @ORM\Column(type="boolean")
         */
        protected $enabled;

        /**
         * @ORM\ManyToMany(targetEntity="Post")
         */
        protected $posts;

        public function __construct()
        {
            $this->posts = new \Doctrine\Common\Collections\ArrayCollection();
        }

        public function __toString()
        {
            return $this->getName();
        }
    }

Comment
~~~~~~~

.. code-block:: php

    <?php

    // src/Tutorial/BlogBundle/Entity/Comment.php
    namespace Tutorial\BlogBundle\Entity;

    use Doctrine\ORM\Mapping as ORM;
    use Symfony\Component\Validator\Constraints as Assert;

    /**
     * @ORM\Entity
     */
    class Comment
    {
        /**
         * @ORM\Id
         * @ORM\Column(type="integer")
         * @ORM\GeneratedValue(strategy="AUTO")
         */
        protected $id;

        /**
         * @ORM\Column(type="string")
         *
         * @Assert\NotBlank()
         */
        protected $name;


        /**
         * @ORM\Column(type="string")
         *
         * @Assert\NotBlank()
         */
        protected $email;

        /**
         * @ORM\Column(type="string")
         */
        protected $url;

        /**
         * @ORM\Column(type="text")
         * @Assert\NotBlank()
         */
        protected $message;

        /**
         * @ORM\ManyToOne(targetEntity="Post")
         */
        protected $post;

        public function __toString()
        {
            return $this->getName();
        }
    }


Generate getters and setters
----------------------------

Fill the entities with getters and setters by running the following command:


.. code-block:: bash

    php app/console doctrine:generate:entities Tutorial

Creating the Database
---------------------

Create the database related to the entities and the mapping by running the following command:

.. code-block:: bash

    php app/console doctrine:schema:update --force