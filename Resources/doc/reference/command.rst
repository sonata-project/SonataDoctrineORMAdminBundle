Command
===========================================================

Now that you would know how to create Admin class for your entity class, but there is a command which help you to do it in a little easier way.

Usage
-----

The ``generate:doctrine:admin`` generates a basic Admin class by using the
metadata mapping of a given entity class:

.. code-block:: bash

    php app/console generate:doctrine:admin AcmeBlogBundle:Post

Required Arguments
------------------

* ``entity``: The entity name given as a shortcut notation containing the
  bundle name in which the entity is located and the name of the entity. For
  example: ``AcmeBlogBundle:Post``:

    .. code-block:: bash

        php app/console generate:doctrine:admin AcmeBlogBundle:Post
