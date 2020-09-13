UPGRADE 3.x
===========

UPGRADE FROM 3.22 to 3.23
=========================

### Sonata\DoctrineORMAdminBundle\Model\ModelManager

- Deprecated `ModelManager::getModelCollectionInstance()`.
- Deprecated `ModelManager::collectionClear()`.
- Deprecated `ModelManager::collectionHasElement()`.
- Deprecated `ModelManager::collectionAddElement()`.
- Deprecated `ModelManager::collectionRemoveElement()`.
- Deprecated `ModelManager::getPaginationParameters()`.
- Deprecated `ModelManager::getSortParameters()`.

UPGRADE FROM 3.21 to 3.22
=========================

### Compatibility with SonataBlockBundle 4.0

We added compatibility with SonataBlockBundle 4.0, make sure you are explicitly declaring your dependency
with `sonata-project/block-bundle` in your composer.json in order to avoid unwanted upgrades.

There is a minimal BC Break on `AuditBlockService`. If you are extending this class (keep in mind that it will become final on 4.0) you should add return type hints to `execute()` and `configureSettings()`.

### Sonata\DoctrineORMAdminBundle\Model\ModelManager

Deprecated `camelize()` method with no replacement.

UPGRADE FROM 3.20 to 3.21
=========================

### Sonata\DoctrineORMAdminBundle\Filter\StringFilter

Deprecated `format` option with no replacement.

UPGRADE FROM 3.19 to 3.20
=========================

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
