<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Sonata\DoctrineORMAdminBundle\Filter\BooleanFilter;
use Sonata\DoctrineORMAdminBundle\Filter\CallbackFilter;
use Sonata\DoctrineORMAdminBundle\Filter\ChoiceFilter;
use Sonata\DoctrineORMAdminBundle\Filter\ClassFilter;
use Sonata\DoctrineORMAdminBundle\Filter\CountFilter;
use Sonata\DoctrineORMAdminBundle\Filter\DateFilter;
use Sonata\DoctrineORMAdminBundle\Filter\DateRangeFilter;
use Sonata\DoctrineORMAdminBundle\Filter\DateTimeFilter;
use Sonata\DoctrineORMAdminBundle\Filter\DateTimeRangeFilter;
use Sonata\DoctrineORMAdminBundle\Filter\EmptyFilter;
use Sonata\DoctrineORMAdminBundle\Filter\ModelAutocompleteFilter;
use Sonata\DoctrineORMAdminBundle\Filter\ModelFilter;
use Sonata\DoctrineORMAdminBundle\Filter\NullFilter;
use Sonata\DoctrineORMAdminBundle\Filter\NumberFilter;
use Sonata\DoctrineORMAdminBundle\Filter\StringFilter;
use Sonata\DoctrineORMAdminBundle\Filter\StringListFilter;
use Sonata\DoctrineORMAdminBundle\Filter\TimeFilter;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set('sonata.admin.orm.filter.type.boolean', BooleanFilter::class)
        ->tag('sonata.admin.filter.type', ['alias' => 'doctrine_orm_boolean']);

    $services->set('sonata.admin.orm.filter.type.callback', CallbackFilter::class)
        ->tag('sonata.admin.filter.type', ['alias' => 'doctrine_orm_callback']);

    $services->set('sonata.admin.orm.filter.type.choice', ChoiceFilter::class)
        ->tag('sonata.admin.filter.type', ['alias' => 'doctrine_orm_choice']);

    $services->set('sonata.admin.orm.filter.type.class', ClassFilter::class)
        ->tag('sonata.admin.filter.type', ['alias' => 'doctrine_orm_class']);

    $services->set('sonata.admin.orm.filter.type.count', CountFilter::class)
        ->tag('sonata.admin.filter.type', ['alias' => 'doctrine_orm_count']);

    $services->set('sonata.admin.orm.filter.type.date', DateFilter::class)
        ->tag('sonata.admin.filter.type', ['alias' => 'doctrine_orm_date']);

    $services->set('sonata.admin.orm.filter.type.date_range', DateRangeFilter::class)
        ->tag('sonata.admin.filter.type', ['alias' => 'doctrine_orm_date_range']);

    $services->set('sonata.admin.orm.filter.type.datetime', DateTimeFilter::class)
        ->tag('sonata.admin.filter.type', ['alias' => 'doctrine_orm_datetime']);

    $services->set('sonata.admin.orm.filter.type.datetime_range', DateTimeRangeFilter::class)
        ->tag('sonata.admin.filter.type', ['alias' => 'doctrine_orm_datetime_range']);

    $services->set('sonata.admin.orm.filter.type.empty', EmptyFilter::class)
        ->tag('sonata.admin.filter.type', ['alias' => 'doctrine_orm_empty']);

    /**
     * NEXT_MAJOR: Remove this service definition.
     *
     * @psalm-suppress DeprecatedClass
     *
     * @see https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1545
     */
    $services->set('sonata.admin.orm.filter.type.model_autocomplete', ModelAutocompleteFilter::class)
        ->tag('sonata.admin.filter.type', ['alias' => 'doctrine_orm_model_autocomplete']);

    $services->set('sonata.admin.orm.filter.type.model', ModelFilter::class)
        ->tag('sonata.admin.filter.type', ['alias' => 'doctrine_orm_model']);

    $services->set('sonata.admin.orm.filter.type.null', NullFilter::class)
        ->tag('sonata.admin.filter.type', ['alias' => 'doctrine_orm_null']);

    $services->set('sonata.admin.orm.filter.type.number', NumberFilter::class)
        ->tag('sonata.admin.filter.type', ['alias' => 'doctrine_orm_number']);

    $services->set('sonata.admin.orm.filter.type.string', StringFilter::class)
        ->tag('sonata.admin.filter.type', ['alias' => 'doctrine_orm_string']);

    $services->set('sonata.admin.orm.filter.type.string_list', StringListFilter::class)
        ->tag('sonata.admin.filter.type', ['alias' => 'doctrine_orm_string_list']);

    $services->set('sonata.admin.orm.filter.type.time', TimeFilter::class)
        ->tag('sonata.admin.filter.type', ['alias' => 'doctrine_orm_time']);
};
