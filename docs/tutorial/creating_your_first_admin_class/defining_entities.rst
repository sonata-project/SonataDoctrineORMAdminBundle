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

    // src/Tutorial/BlogBundle/Entity/Author.php

    namespace Tutorial\BlogBundle\Entity;

    use Doctrine\DBAL\Types\Types;
    use Doctrine\ORM\Mapping as ORM;

    #[ORM\Embeddable]
    class Author
    {
        #[ORM\Column(type: Types::STRING)]
        private $name;

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

    // src/Tutorial/BlogBundle/Entity/Post.php

    namespace Tutorial\BlogBundle\Entity;

    use Doctrine\DBAL\Types\Types;
    use Doctrine\ORM\Mapping as ORM;
    use Symfony\Component\Validator\Constraints as Assert;

    #[ORM\Entity]
    class Post
    {
        #[ORM\Id]
        #[ORM\Column(type: Types::INTEGER)]
        #[ORM\GeneratedValue]
        private $id;

        #[ORM\Column(type: Types::STRING)]
        #[Assert\NotBlank]
        #[Assert\Length(min: 10, max: 255)]
        private $title;

        #[ORM\Column(type: Types::TEXT)]
        private $abstract;

        #[ORM\Column(type: Types::TEXT)]
        #[Assert\NotBlank]
        private $content;

        #[ORM\Column(type: Types::BOOLEAN)]
        private $enabled;

        #[ORM\Column(type: Types::DATETIME_MUTABLE)]
        private $created_at;

        #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
        private $updated_at;

        #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'post')]
        private $comments;

        #[ORM\OneToMany(targetEntity: Tag::class)]
        private $tags;

        #[ORM\Embedded(class: Author::class)]
        private $author;

        public function __construct()
        {
            $this->tags = new \Doctrine\Common\Collections\ArrayCollection();
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

    // src/Tutorial/BlogBundle/Entity/Tag.php

    namespace Tutorial\BlogBundle\Entity;

    use Doctrine\DBAL\Types\Types;
    use Doctrine\ORM\Mapping as ORM;
    use Symfony\Component\Validator\Constraints as Assert;

    #[ORM\Entity]
    class Tag
    {
        #[ORM\Id]
        #[ORM\Column(type: Types::INTEGER)]
        #[ORM\GeneratedValue]
        private $id;

        #[ORM\Column(type: Types::STRING)]
        #[Assert\NotBlank]
        private $name;

        #[ORM\Column(type: Types::BOOLEAN)]
        private $enabled;

        #[ORM\ManyToMany(targetEntity: Post::class)]
        private $posts;

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

    // src/Tutorial/BlogBundle/Entity/Comment.php

    namespace Tutorial\BlogBundle\Entity;

    use Doctrine\DBAL\Types\Types;
    use Doctrine\ORM\Mapping as ORM;
    use Symfony\Component\Validator\Constraints as Assert;

    #[ORM\Entity]
    class Comment
    {
        #[ORM\Id]
        #[ORM\Column(type: Types::INTEGER)]
        #[ORM\GeneratedValue]
        private $id;

        #[ORM\Column(type: Types::STRING)]
        #[Assert\NotBlank]
        private $name;

        #[ORM\Column(type: Types::STRING)]
        #[Assert\NotBlank]
        private $email;

        #[ORM\Column(type: Types::STRING)]
        private $url;

        #[ORM\Column(type: Types::TEXT)]
        #[Assert\NotBlank]
        private $message;

        #[ORM\ManyToOne(targetEntity: Post::class)]
        private $post;

        public function __toString()
        {
            return $this->getName();
        }
    }

.. note::

    For advanced usage, ``$id`` might be implemented as an object. The bundle will automatically resolve its string
    representation from the ID object using ``$entity->getId()->__toString()`` (if implemented) when needed
    (e.g., for generating url / rendering).

    For example, in a use case where `InnoDB-optimised binary UUIDs`_ is implemented::

        use Doctrine\DBAL\Types\Types;
        use Doctrine\ORM\Mapping as ORM;
        use Ramsey\Uuid\Doctrine\UuidOrderedTimeGenerator;
        use Ramsey\Uuid\UuidInterface;

        class Comment
        {
            #[ORM\Id]
            #[ORM\Column(type: Types::INTEGER)]
            #[ORM\GeneratedValue(strategy: 'CUSTOM')]
            #[ORM\CustomIdGenerator(class: UuidOrderedTimeGenerator::class)]
            private ?UuidInterface $id = null;

            // ...
        }

    As ``$comment->getId()`` returns an object of ``\Ramsey\Uuid\UuidInterface`` and the bundle recognizes
    that it has offered a ``__toString`` method, ``$comment->getId()->__toString()`` is called to resolve
    the ID string value as part of the entity url generation.

Generate getters and setters
----------------------------

Fill the entities with getters and setters by running the following command:

.. code-block:: bash

    bin/console doctrine:generate:entities Tutorial

Creating the Database
---------------------

Create the database related to the entities and the mapping by running the following command:

.. code-block:: bash

    bin/console doctrine:schema:update --force

.. _`InnoDB-optimised binary UUIDs`: https://github.com/ramsey/uuid-doctrine#innodb-optimised-binary-uuids
