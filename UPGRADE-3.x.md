UPGRADE 3.x
===========

UPGRADE FROM 3.x to 3.x
=======================

### Sonata\DoctrineORMAdminBundle\Admin\FieldDescription

Deprecated `getTargetEntity()`, use `getTargetModel()` instead.

### Sonata\DoctrineORMAdminBundle\Model\ModelManager

Deprecated passing `null` as argument 2 for `find()`.
Deprecated passing `null` or an object which is in state new or removed as argument 1 for `getNormalizedIdentifier()`.
Deprecated passing `null` as argument 1 for `getUrlSafeIdentifier()`.

UPGRADE FROM 3.0 to 3.1
=======================

### Tests

All files under the ``Tests`` directory are now correctly handled as internal test classes.
You can't extend them anymore, because they are only loaded when running internal tests.
More information can be found in the [composer docs](https://getcomposer.org/doc/04-schema.md#autoload-dev).
