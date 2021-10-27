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
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

/**
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 * @author Michael Williams <michael.williams@funsational.com>
 */
final class SonataDoctrineORMAdminExtension extends AbstractSonataAdminExtension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configs = $this->fixTemplatesConfiguration($configs, $container);

        $configuration = new Configuration();
        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, $configs);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('doctrine_orm.php');
        $loader->load('doctrine_orm_filter_types.php');

        $bundles = $container->getParameter('kernel.bundles');
        \assert(\is_array($bundles));

        if (isset($bundles['SimpleThingsEntityAuditBundle'])) {
            $loader->load('audit.php');

            $container->setParameter('sonata_doctrine_orm_admin.audit.force', $config['audit']['force']);
        }

        $loader->load('security.php');

        $container->setParameter('sonata_doctrine_orm_admin.entity_manager', $config['entity_manager']);

        $container->setParameter('sonata_doctrine_orm_admin.templates', $config['templates']);

        // define the templates
        $container->getDefinition('sonata.admin.builder.orm_list')
            ->replaceArgument(1, $config['templates']['types']['list']);

        $container->getDefinition('sonata.admin.builder.orm_show')
            ->replaceArgument(1, $config['templates']['types']['show']);
    }
}
