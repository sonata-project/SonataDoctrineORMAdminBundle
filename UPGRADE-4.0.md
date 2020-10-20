UPGRADE FROM 3.X to 3.0
=======================

## Deprecations

All the deprecated code introduced on 3.x is removed on 4.0.

Please read [3.x](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/tree/3.x) upgrade guides for more information.

See also the [diff code](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.x...4.0.0).

## ModelManager

The default route name for association links is `show` instead of `edit`.

If you want to keep the old behaviour, you SHOULD override the `getNewFieldDescriptionInstance()` method.
