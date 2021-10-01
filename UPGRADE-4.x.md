UPGRADE 4.x
===========

UPGRADE FROM 4.x to 4.x
=======================

### Sonata\DoctrineORMAdminBundle\Filter\ModelAutocompleteFilter

The `Sonata\DoctrineORMAdminBundle\Filter\ModelAutocompleteFilter` filter is deprecated.

Instead of
```php
->add('foo', ModelAutocompleteFilter::class)
```
use
```php
->add('foo', ModelFilter::class, [
     'field_type' => ModelAutocompleteType::class,
])
```
