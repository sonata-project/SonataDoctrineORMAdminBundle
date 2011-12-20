Defining Entities
=================

This tutorial uses the more verbose xml format of defining entities, but any
metadata driver will work fine. The ``AdminBundle`` simply interacts with the
entities as provided by Doctrine.

Model definition
----------------

No we need to create the entities that will be used in the blog:

Post
~~~~

.. code-block:: php

    <?php
    // src/Tutorial/BlogBundle/Entity/Post.php
    namespace Tutorial\BlogBundle\Entity;

    use Doctrine\ORM\Mapping as ORM;

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
         * @ORM\Column(type="string", length="255")
         * @validation:Validation({
         *      @validation:MinLength(limit=10),
         *      @validation:NotBlank(),
         *      @validation:MaxLength(limit=255)
         * })
         */
        protected $title;

        /**
         * @ORM\Column(type="text")
         */
        protected $abstract;


        /**
         * @ORM\Column(type="text")
         * @validation:Validation({
         *      @validation:NotBlank()
         * })
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

        public function __toString() 
        {
          return $this->getTitle();
        }

        public function __construct()
        {
            $this->tags     = new \Doctrine\Common\Collections\ArrayCollection;
            $this->comments = new \Doctrine\Common\Collections\ArrayCollection;
            $this->created_at = new \DateTime("now");;
        }

Tag
~~~

.. code-block:: php

    <?php
    // src/Tutorial/BlogBundle/Entity/Tag.php
    namespace Tutorial\BlogBundle\Entity;

    use Doctrine\ORM\Mapping as ORM;

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
         * @validation:Validation({
         *      @validation:NotBlank()
         * })
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

        public function __toString() 
        {
          return $this->getName();
        }

        public function __construct()
        {
            $this->posts = new \Doctrine\Common\Collections\ArrayCollection();
        }

Comment
~~~~~~~

.. code-block:: php

    <?php
    // src/Tutorial/BlogBundle/Entity/Comment.php
    namespace Tutorial\BlogBundle\Entity;

    use Doctrine\ORM\Mapping as ORM;

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
         * @validation:Validation({
         *      @validation:NotBlank()
         * })
         */
        protected $name;


        /**
         * @ORM\Column(type="string")
         * @validation:Validation({
         *      @validation:NotBlank()
         * })
         */
        protected $email;


        /**
         * @ORM\Column(type="string")
         */
        protected $url;


        /**
         * @ORM\Column(type="text")
         * @validation:Validation({
         *      @validation:NotBlank()
         * })
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



Generate getter and setter
--------------------------

Fill the entities with getters and setters running the command:

  php app/console doctrine:generate:entities Tutorial

Creating Database
-----------------

Create the database related to the entities and the mapping running:

  php app/console doctrine:schema:update --force