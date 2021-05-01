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

namespace Sonata\DoctrineORMAdminBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * NEXT_MAJOR: Remove the "since" part of the internal annotation.
 *
 * @internal since sonata-project/admin-bundle version 4.0
 *
 * @final since sonata-project/doctrine-orm-admin-bundle 3.24
 *
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class AddGuesserCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        // ListBuilder
        $definition = $container->getDefinition('sonata.admin.guesser.orm_list_chain');
        $services = [];
        foreach ($container->findTaggedServiceIds('sonata.admin.guesser.orm_list') as $id => $attributes) {
            $services[] = new Reference($id);
        }

        $definition->replaceArgument(0, $services);

        // DatagridBuilder
        $definition = $container->getDefinition('sonata.admin.guesser.orm_datagrid_chain');
        $services = [];
        foreach ($container->findTaggedServiceIds('sonata.admin.guesser.orm_datagrid') as $id => $attributes) {
            $services[] = new Reference($id);
        }

        $definition->replaceArgument(0, $services);

        // ShowBuilder
        $definition = $container->getDefinition('sonata.admin.guesser.orm_show_chain');
        $services = [];
        foreach ($container->findTaggedServiceIds('sonata.admin.guesser.orm_show') as $id => $attributes) {
            $services[] = new Reference($id);
        }

        $definition->replaceArgument(0, $services);
    }
}
