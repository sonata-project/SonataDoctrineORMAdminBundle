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

use Doctrine\ORM\EntityManager;
use Sonata\AdminBundle\FieldDescription\TypeGuesserChain;
use Sonata\DoctrineORMAdminBundle\Builder\DatagridBuilder;
use Sonata\DoctrineORMAdminBundle\Builder\FormContractor;
use Sonata\DoctrineORMAdminBundle\Builder\ListBuilder;
use Sonata\DoctrineORMAdminBundle\Builder\ShowBuilder;
use Sonata\DoctrineORMAdminBundle\Exporter\DataSource;
use Sonata\DoctrineORMAdminBundle\FieldDescription\FieldDescriptionFactory;
use Sonata\DoctrineORMAdminBundle\FieldDescription\FilterTypeGuesser;
use Sonata\DoctrineORMAdminBundle\FieldDescription\TypeGuesser;
use Sonata\DoctrineORMAdminBundle\Model\ModelManager;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->services()

        ->set('sonata.admin.entity_manager', EntityManager::class)
            ->args([
                param('sonata_doctrine_orm_admin.entity_manager'),
            ])
            ->factory([service('doctrine'), 'getManager'])

        ->set('sonata.admin.manager.orm', ModelManager::class)
            ->public()
            ->tag('sonata.admin.manager')
            ->args([
                service('doctrine'),
                service('property_accessor'),
            ])

        ->set('sonata.admin.builder.orm_form', FormContractor::class)
            ->args([
                service('form.factory'),
                service('form.registry'),
            ])

        ->set('sonata.admin.builder.orm_list', ListBuilder::class)
            ->args([
                service('sonata.admin.guesser.orm_list_chain'),
                abstract_arg('list type templates'),
            ])

        ->set('sonata.admin.guesser.orm_list', TypeGuesser::class)
            ->tag('sonata.admin.guesser.orm_list')

        ->set('sonata.admin.guesser.orm_list_chain', TypeGuesserChain::class)
            ->args([
                abstract_arg('guessers'),
            ])

        ->set('sonata.admin.builder.orm_show', ShowBuilder::class)
            ->args([
                service('sonata.admin.guesser.orm_show_chain'),
                abstract_arg('show type templates'),
            ])

        ->set('sonata.admin.guesser.orm_show', TypeGuesser::class)
            ->tag('sonata.admin.guesser.orm_show')

        ->set('sonata.admin.guesser.orm_show_chain', TypeGuesserChain::class)
            ->args([
                abstract_arg('guessers'),
            ])

        ->set('sonata.admin.builder.orm_datagrid', DatagridBuilder::class)
            ->args([
                service('form.factory'),
                service('sonata.admin.builder.filter.factory'),
                service('sonata.admin.guesser.orm_datagrid_chain'),
                param('form.type_extension.csrf.enabled'),
            ])

        ->set('sonata.admin.guesser.orm_datagrid', FilterTypeGuesser::class)
            ->tag('sonata.admin.guesser.orm_datagrid')

        ->set('sonata.admin.guesser.orm_datagrid_chain', TypeGuesserChain::class)
            ->args([
                abstract_arg('guessers'),
            ])

        ->set('sonata.admin.data_source.orm', DataSource::class)

        ->set('sonata.admin.field_description_factory.orm', FieldDescriptionFactory::class)
            ->args([
                service('doctrine'),
            ]);
};
