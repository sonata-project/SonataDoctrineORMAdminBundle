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

namespace Sonata\DoctrineORMAdminBundle\DependencyInjection;

use Sonata\AdminBundle\DependencyInjection\AbstractSonataAdminExtension;
use Sonata\DoctrineORMAdminBundle\Filter\EmptyFilter;
use Symfony\Component\Config\Definition\BaseNode;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

/**
 * @final since sonata-project/doctrine-orm-admin-bundle 3.24
 *
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 * @author Michael Williams <michael.williams@funsational.com>
 */
class SonataDoctrineORMAdminExtension extends AbstractSonataAdminExtension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configs = $this->fixTemplatesConfiguration($configs, $container);

        $configuration = new Configuration();
        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('doctrine_orm.xml');
        $loader->load('doctrine_orm_filter_types.xml');

        $bundles = $container->getParameter('kernel.bundles');

        if (isset($bundles['SimpleThingsEntityAuditBundle'])) {
            $loader->load('audit.xml');

            $container->setParameter('sonata_doctrine_orm_admin.audit.force', $config['audit']['force']);
        }

        $loader->load('security.xml');

        $container->setParameter('sonata_doctrine_orm_admin.entity_manager', $config['entity_manager']);

        $container->setParameter('sonata_doctrine_orm_admin.templates', $config['templates']);

        // define the templates
        $container->getDefinition('sonata.admin.builder.orm_list')
            ->replaceArgument(1, $config['templates']['types']['list']);

        $container->getDefinition('sonata.admin.builder.orm_show')
            ->replaceArgument(1, $config['templates']['types']['show']);

        // NEXT_MAJOR: remove this block.
        $deprecatedEmptyFilterDefinition = $container->getDefinition(EmptyFilter::class);
        if (method_exists(BaseNode::class, 'getDeprecation')) {
            // Symfony 5.1+
            $deprecatedEmptyFilterDefinition->setDeprecated(
                'sonata-project/doctrine-orm-admin-bundle',
                '3.x',
                'The "%service_id%" service is deprecated since sonata-project/doctrine-orm-admin-bundle version 3.x and will be removed in 4.0.'
            );
        } else {
            // Symfony < 5.1
            $deprecatedEmptyFilterDefinition->setDeprecated(
                'The "%service_id%" service is deprecated since sonata-project/doctrine-orm-admin-bundle version 3.x and will be removed in 4.0.'
            );
        }
    }
}
