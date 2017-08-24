<?php

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
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 * @author Michael Williams <michael.williams@funsational.com>
 */
class SonataDoctrineORMAdminExtension extends AbstractSonataAdminExtension
{
    /**
     * @param array            $configs   An array of configuration settings
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configs = $this->fixTemplatesConfiguration($configs, $container);

        $configuration = new Configuration();
        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('doctrine_orm.xml');
        $loader->load('doctrine_orm_filter_types.xml');

        // TODO: Go back on xml configuration when bumping requirements to SF 2.6+
        $doctrineEMDefinition = $container->getDefinition('sonata.admin.entity_manager');
        // NEXT_MAJOR: remove when dropping Symfony <2.1 support
        $doctrineFactoryMethod = method_exists('Doctrine\Bundle\DoctrineBundle\Registry', 'getManager')
            ? 'getManager'
            : 'getEntityManager';
        if (method_exists($doctrineEMDefinition, 'setFactory')) {
            $doctrineEMDefinition->setFactory(array(new Reference('doctrine'), $doctrineFactoryMethod));
        } else {
            $doctrineEMDefinition->setFactoryService('doctrine');
            $doctrineEMDefinition->setFactoryMethod($doctrineFactoryMethod);
        }

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
    }
}
