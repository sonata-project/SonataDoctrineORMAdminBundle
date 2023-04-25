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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

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
use Sonata\DoctrineORMAdminBundle\Filter\UidFilter;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->services()

        ->set('sonata.admin.orm.filter.type.boolean', BooleanFilter::class)
            ->tag('sonata.admin.filter.type', ['alias' => 'doctrine_orm_boolean'])

        ->set('sonata.admin.orm.filter.type.callback', CallbackFilter::class)
            ->tag('sonata.admin.filter.type', ['alias' => 'doctrine_orm_callback'])

        ->set('sonata.admin.orm.filter.type.choice', ChoiceFilter::class)
            ->tag('sonata.admin.filter.type', ['alias' => 'doctrine_orm_choice'])

        ->set('sonata.admin.orm.filter.type.class', ClassFilter::class)
            ->tag('sonata.admin.filter.type', ['alias' => 'doctrine_orm_class'])

        ->set('sonata.admin.orm.filter.type.count', CountFilter::class)
            ->tag('sonata.admin.filter.type', ['alias' => 'doctrine_orm_count'])

        ->set('sonata.admin.orm.filter.type.date', DateFilter::class)
            ->tag('sonata.admin.filter.type', ['alias' => 'doctrine_orm_date'])

        ->set('sonata.admin.orm.filter.type.date_range', DateRangeFilter::class)
            ->tag('sonata.admin.filter.type', ['alias' => 'doctrine_orm_date_range'])

        ->set('sonata.admin.orm.filter.type.datetime', DateTimeFilter::class)
            ->tag('sonata.admin.filter.type', ['alias' => 'doctrine_orm_datetime'])

        ->set('sonata.admin.orm.filter.type.datetime_range', DateTimeRangeFilter::class)
            ->tag('sonata.admin.filter.type', ['alias' => 'doctrine_orm_datetime_range'])

        ->set('sonata.admin.orm.filter.type.empty', EmptyFilter::class)
            ->tag('sonata.admin.filter.type', ['alias' => 'doctrine_orm_empty'])

        ->set('sonata.admin.orm.filter.type.model', ModelFilter::class)
            ->tag('sonata.admin.filter.type', ['alias' => 'doctrine_orm_model'])

        ->set('sonata.admin.orm.filter.type.null', NullFilter::class)
            ->tag('sonata.admin.filter.type', ['alias' => 'doctrine_orm_null'])

        ->set('sonata.admin.orm.filter.type.number', NumberFilter::class)
            ->tag('sonata.admin.filter.type', ['alias' => 'doctrine_orm_number'])

        ->set('sonata.admin.orm.filter.type.string', StringFilter::class)
            ->tag('sonata.admin.filter.type', ['alias' => 'doctrine_orm_string'])

        ->set('sonata.admin.orm.filter.type.string_list', StringListFilter::class)
            ->tag('sonata.admin.filter.type', ['alias' => 'doctrine_orm_string_list'])

        ->set('sonata.admin.orm.filter.type.time', TimeFilter::class)
            ->tag('sonata.admin.filter.type', ['alias' => 'doctrine_orm_time'])

        ->set('sonata.admin.orm.filter.type.uid', UidFilter::class)
            ->tag('sonata.admin.filter.type', ['alias' => 'doctrine_orm_uid']);

    /**
     * NEXT_MAJOR: Remove this service definition.
     *
     * @psalm-suppress DeprecatedClass
     *
     * @see https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1545
     */
    $containerConfigurator->services()->set('sonata.admin.orm.filter.type.model_autocomplete', ModelAutocompleteFilter::class)
        ->tag('sonata.admin.filter.type', ['alias' => 'doctrine_orm_model_autocomplete']);
};
