UPGRADE FROM 3.X to 4.0
=======================

## Deprecations

All the deprecated code introduced on 3.x is removed on 4.0.

Please read [3.x](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/tree/3.x) upgrade guides for more information.

See also the [diff code](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.x...4.0.0).

# FieldDescription and TypeGuesser

Moved `Sonata\DoctrineORMAdminBundle\Admin\FieldDescription` to `Sonata\DoctrineORMAdminBundle\FieldDescription\FieldDescription`.
Moved `Sonata\DoctrineORMAdminBundle\Guesser\FilterTypeGuesser` to `Sonata\DoctrineORMAdminBundle\Guesser\FilterTypeGuesser`.
Moved `Sonata\DoctrineORMAdminBundle\Guesser\TypeGuesser` to `Sonata\DoctrineORMAdminBundle\Guesser\TypeGuesser`.
