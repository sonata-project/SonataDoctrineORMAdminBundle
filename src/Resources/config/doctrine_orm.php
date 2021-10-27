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
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ReferenceConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    // Use "service" function for creating references to services when dropping support for Symfony 4.4
    // Use "param" function for creating references to parameters when dropping support for Symfony 5.1
    $services = $containerConfigurator->services();

    $services->set('sonata.admin.entity_manager', EntityManager::class)
        ->args([
            '%sonata_doctrine_orm_admin.entity_manager%',
        ])
        ->factory([
            new ReferenceConfigurator('doctrine'),
            'getManager',
        ]);

    $services->set('sonata.admin.manager.orm', ModelManager::class)
        ->public()
        ->tag('sonata.admin.manager')
        ->args([
            new ReferenceConfigurator('doctrine'),
            new ReferenceConfigurator('property_accessor'),
        ]);

    $services->set('sonata.admin.builder.orm_form', FormContractor::class)
        ->args([
            new ReferenceConfigurator('form.factory'),
            new ReferenceConfigurator('form.registry'),
        ]);

    $services->set('sonata.admin.builder.orm_list', ListBuilder::class)
        ->args([
            new ReferenceConfigurator('sonata.admin.guesser.orm_list_chain'),
            [], // list type templates
        ]);

    $services->set('sonata.admin.guesser.orm_list', TypeGuesser::class)
        ->tag('sonata.admin.guesser.orm_list');

    $services->set('sonata.admin.guesser.orm_list_chain', TypeGuesserChain::class)
        ->args([
            [], // guessers
        ]);

    $services->set('sonata.admin.builder.orm_show', ShowBuilder::class)
        ->args([
            new ReferenceConfigurator('sonata.admin.guesser.orm_show_chain'),
            [], // show type templates
        ]);

    $services->set('sonata.admin.guesser.orm_show', TypeGuesser::class)
        ->tag('sonata.admin.guesser.orm_show');

    $services->set('sonata.admin.guesser.orm_show_chain', TypeGuesserChain::class)
        ->args([
            [], // guessers
        ]);

    $services->set('sonata.admin.builder.orm_datagrid', DatagridBuilder::class)
        ->args([
            new ReferenceConfigurator('form.factory'),
            new ReferenceConfigurator('sonata.admin.builder.filter.factory'),
            new ReferenceConfigurator('sonata.admin.guesser.orm_datagrid_chain'),
            '%form.type_extension.csrf.enabled%',
        ]);

    $services->set('sonata.admin.guesser.orm_datagrid', FilterTypeGuesser::class)
        ->tag('sonata.admin.guesser.orm_datagrid');

    $services->set('sonata.admin.guesser.orm_datagrid_chain', TypeGuesserChain::class)
        ->args([
            [], // guessers
        ]);

    $services->set('sonata.admin.data_source.orm', DataSource::class);

    $services->set('sonata.admin.field_description_factory.orm', FieldDescriptionFactory::class)
        ->args([
            new ReferenceConfigurator('doctrine'),
        ]);
};
