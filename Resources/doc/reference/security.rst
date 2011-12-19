Security
========

See the security part in the documentation of the Admin bundle. When using ACL, the object ACL's need to be created
to have the ACL implementation working correctly. Otherwise access can be denied because the access rules are not found
for an object.

If you have Admin classes, you can generate the object ACL rules for each object of an admin:

.. code-block:: sh

    $ php app/console sonata:admin:generate-object-acl

Optionally, you can specify an object owner, this will be asked when the command is run.