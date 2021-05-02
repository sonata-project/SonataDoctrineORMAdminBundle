UPGRADE 3.x
===========

UPGRADE FROM 3.33 to 3.34
=========================

### Sonata\DoctrineORMAdminBundle\Filter\CallbackFilter

Not adding `Sonata\AdminBundle\Filter\Model\FilterData` as type declaration of argument 4 of the callable passed to
`Sonata\DoctrineORMAdminBundle\Filter\CallbackFilter` is deprecated. In version 4.0 this argument will be an instance
of `Sonata\AdminBundle\Filter\Model\FilterData`.

UPGRADE FROM 3.32 to 3.33
=========================

### Sonata\DoctrineORMAdminBundle\Filter\StringFilter

The option "case_sensitive" is deprecated in favor of "force_case_insensitivity".
You must pass `true` in this option when you need to force the matching criteria
to avoid honoring the case sensitivity in the filter values. Any other values than
`true` will cause the database to use its default behavior.
The option "case_sensitive" will be respected only if "force_case_insensitivity"
is not set.

UPGRADE FROM 3.31 to 3.32
=========================

### Deprecated Sonata\DoctrineORMAdminBundle\Model\ModelManager::modelReverseTransform()

Use `ModelManager::reverseTransform()` instead.

UPGRADE FROM 3.26 to 3.27
=========================

### Sonata\DoctrineORMAdminBundle\Filter\EmptyFilter

The EmptyFilter is deprecated, use NullFilter instead.

### Sonata\DoctrineORMAdminBundle\Model\ModelManager

Deprecate `modelTransform()`, `getDefaultPerPageOptions()`, `getDefaultSortValues()` and `getDataSourceIterator()` with no replacement.

### Sonata\DoctrineORMAdminBundle\Filter\Filter

Deprecate passing an instance of `Sonata\AdminBundle\Datagrid\ProxyQueryInterface`
which is not an instance of `Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery` as
argument 1 to the `Sonata\DoctrineORMAdminBundle\Filter\Filter::filter()` method.

UPGRADE FROM 3.25 to 3.26
=========================

### Sonata\DoctrineORMAdminBundle\Model\ModelManager

Previously passing an object which is in state new or removed as argument 1 for `getNormalizedIdentifier()` was deprecated and would throw an exception in 4.0. Since throwing an exception is not allowed (and returning `null` is still allowed), the deprecation is removed.

UPGRADE FROM 3.24 to 3.25
=========================

### Added full support for `\DateTimeImmutable` in filters extending `Sonata\DoctrineORMAdminBundle\Filter\AbstractDateFilter`

- `Sonata\DoctrineORMAdminBundle\Filter\DateFilter`
- `Sonata\DoctrineORMAdminBundle\Filter\DateRangeFilter`
- `Sonata\DoctrineORMAdminBundle\Filter\DateTimeFilter`
- `Sonata\DoctrineORMAdminBundle\Filter\DateTimeRangeFilter`
- `Sonata\DoctrineORMAdminBundle\Filter\TimeFilter`

Previous to this change, only the instances of `\DateTime` were manipulated in these
filters to set the time under determined circumstances. If you are using them with instances
of `\DateTimeImmutable`, be aware of this change in order to confirm if you must update
your implementation.

### Sonata\DoctrineORMAdminBundle\Filter\CallbackFilter

Deprecate the usage of a callback which does not return a boolean value.

Previously, this was valid:
```php
function callback(ProxyQueryInterface $queryBuilder, string $alias, string $field, array $value)
{
    if (!$value['value']) {
        return;
    }

    // ...

    return true;
}
```
To remove the deprecation, please update the code this way:
```php
function callback(ProxyQueryInterface $queryBuilder, string $alias, string $field, array $value): bool
{
    if (!$value['value']) {
        return false;
    }

    // ...

    return true;
}
```

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
